import fs from 'fs';
import fs_extra from 'fs-extra';
import { join } from 'path';
import { pipeline } from 'stream/promises';
import { promisify } from 'util';
import unzip from 'yauzl';
import zlib from 'zlib';

const { removeSync, ensureDirSync } = fs_extra;
const inflateRaw = promisify(zlib.inflateRaw);

/**
 * Buffers a readable stream using flowing-mode events.
 *
 * yauzl's entry streams are classic streams that stall when consumed via async
 * iteration, so they are drained with plain `data`/`end` listeners here.
 *
 * @param {import('stream').Readable} stream
 * @returns {Promise<Buffer>}
 */
function readStreamToBuffer(stream) {
    return new Promise((resolve, reject) => {
        const chunks = [];

        stream.on('data', (chunk) => chunks.push(chunk));
        stream.on('end', () => resolve(Buffer.concat(chunks)));
        stream.on('error', reject);
    });
}

const isBuilding = Boolean(process.env.NATIVEPHP_BUILDING);
const phpBinaryPath = process.env.NATIVEPHP_PHP_BINARY_PATH;
const phpVersion = process.env.NATIVEPHP_PHP_BINARY_VERSION;

// Differentiates for Serving and Building
const isArm64 = isBuilding ? process.argv.includes('--arm64') : process.arch.includes('arm64');
const isWindows = isBuilding ? process.argv.includes('--win') : process.platform.includes('win32');
const isLinux = isBuilding ? process.argv.includes('--linux') : process.platform.includes('linux');
const isDarwin = isBuilding ? process.argv.includes('--mac') : process.platform.includes('darwin');

// false because string mapping is done in is{OS} checks
const platform = {
    os: false,
    arch: false,
    phpBinary: 'php',
};

if (isWindows) {
    platform.os = 'win';
    platform.arch = 'x64';
    platform.phpBinary += '.exe';
}

if (isLinux) {
    platform.os = 'linux';
    platform.arch = 'x64';
}

if (isDarwin) {
    platform.os = 'mac';
    platform.arch = 'x64';
}

if (isArm64) {
    platform.arch = 'arm64';
}

// isBuilding overwrites platform to the desired architecture
if (isBuilding) {
    // Only one will be used by the configured build commands in package.json
    platform.arch = process.argv.includes('--x64') ? 'x64' : platform.arch;
    platform.arch = process.argv.includes('--arm64') ? 'arm64' : platform.arch;
}

const phpVersionZip = 'php-' + phpVersion + '.zip';
const binarySrcDir = join(phpBinaryPath, platform.os, platform.arch, phpVersionZip);
const binaryDestDir = join(process.env.NATIVEPHP_BUILD_PATH, 'php');

console.log('Binary Source: ', binarySrcDir);
console.log('Binary Filename: ', platform.phpBinary);
console.log('PHP version: ' + phpVersion);

const DEFLATED = 8;

/**
 * Extracts every entry of the PHP archive to the given binary path.
 *
 * Deflated entries are read raw and inflated in one shot. Streaming inflate
 * (`zlib.createInflateRaw()`, which is what yauzl uses internally) stalls a few
 * kilobytes short of the end on current Node releases and never emits `end`,
 * silently leaving a truncated, unrunnable PHP binary behind.
 *
 * The returned promise settles only once the last entry has been flushed to
 * disk, so the process cannot exit mid-extraction.
 *
 * @param {string} zipPath
 * @param {string} binaryPath
 * @returns {Promise<void>}
 */
function extractPhpBinary(zipPath, binaryPath) {
    return new Promise((resolve, reject) => {
        unzip.open(zipPath, { lazyEntries: true }, (openError, zipfile) => {
            if (openError) {
                reject(openError);

                return;
            }

            zipfile.on('error', reject);
            zipfile.on('end', resolve);

            zipfile.on('entry', (entry) => {
                const isDeflated = entry.compressionMethod === DEFLATED;

                // yauzl rejects a `decompress` option on stored entries.
                const readOptions = isDeflated ? { decompress: false } : {};

                zipfile.openReadStream(entry, readOptions, async (readError, readStream) => {
                    if (readError) {
                        reject(readError);

                        return;
                    }

                    try {
                        if (isDeflated) {
                            fs.writeFileSync(binaryPath, await inflateRaw(await readStreamToBuffer(readStream)));
                        } else {
                            await pipeline(readStream, fs.createWriteStream(binaryPath));
                        }

                        const written = fs.statSync(binaryPath).size;

                        if (written !== entry.uncompressedSize) {
                            throw new Error(
                                `Extracted ${entry.fileName} is ${written} bytes, expected ${entry.uncompressedSize}.`,
                            );
                        }

                        zipfile.readEntry();
                    } catch (streamError) {
                        reject(streamError);
                    }
                });
            });

            zipfile.readEntry();
        });
    });
}

if (platform.phpBinary) {
    const binaryPath = join(binaryDestDir, platform.phpBinary);

    try {
        console.log('Unzipping PHP binary from ' + binarySrcDir + ' to ' + binaryDestDir);
        removeSync(binaryDestDir);

        ensureDirSync(binaryDestDir);

        await extractPhpBinary(binarySrcDir, binaryPath);

        console.log('Copied PHP binary to ', binaryPath);

        // Add execute permissions
        fs.chmodSync(binaryPath, 0o755);
    } catch (e) {
        console.error('Error copying PHP binary', e);
        process.exitCode = 1;
    }
}
