<?php

declare(strict_types=1);

test('returns a successful response', function (): void {
    $response = $this->followingRedirects()->get(route('home'));

    $response->assertOk();
});
