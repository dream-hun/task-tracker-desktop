export type TaskStatus = 'pending' | 'in_progress' | 'completed';

export type TaskPriority = 'low' | 'medium' | 'high';

export type Task = {
    id: number;
    user_id: number;
    title: string;
    description: string | null;
    status: TaskStatus;
    priority: TaskPriority;
    due_date: string | null;
    completed_at: string | null;
    is_overdue: boolean;
    created_at: string;
    updated_at: string;
};

export type TaskFilters = {
    search: string | null;
    status: TaskStatus | null;
    priority: TaskPriority | null;
};

export type TaskStats = {
    total: number;
    pending: number;
    in_progress: number;
    completed: number;
    overdue: number;
};
