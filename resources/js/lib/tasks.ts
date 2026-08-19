import {
    formatDate,
    formatTimestamp,
    parseDateValue,
    toDateValue,
} from '@/lib/dates';
import type { TaskPriority, TaskStatus } from '@/types';

export const taskStatusLabels: Record<TaskStatus, string> = {
    pending: 'Pending',
    in_progress: 'In progress',
    completed: 'Completed',
};

export const taskPriorityLabels: Record<TaskPriority, string> = {
    low: 'Low',
    medium: 'Medium',
    high: 'High',
};

export const taskStatusClasses: Record<TaskStatus, string> = {
    pending:
        'border-neutral-200 bg-neutral-50 text-neutral-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200',
    in_progress:
        'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300',
    completed:
        'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
};

export const taskPriorityClasses: Record<TaskPriority, string> = {
    low: 'border-neutral-200 bg-neutral-50 text-neutral-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
    medium: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300',
    high: 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300',
};

/**
 * Read a `Y-m-d` due date as a local date, so it never shifts a day across time zones.
 */
export function parseDueDate(dueDate: string): Date {
    return parseDateValue(dueDate);
}

/**
 * Write a local date back as the `Y-m-d` value the server expects.
 */
export function toDueDateValue(date: Date): string {
    return toDateValue(date);
}

/**
 * Format a `Y-m-d` due date without shifting it across time zones.
 */
export function formatDueDate(dueDate: string): string {
    return formatDate(dueDate);
}

/**
 * Format the moment a task was completed in the viewer's time zone.
 */
export function formatCompletedAt(completedAt: string): string {
    return formatTimestamp(completedAt);
}
