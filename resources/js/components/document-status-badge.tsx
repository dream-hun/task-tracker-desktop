import { Badge } from '@/components/ui/badge';
import { documentStatusClasses, documentStatusLabels } from '@/lib/documents';
import { cn } from '@/lib/utils';
import type { DocumentStatus } from '@/types';

type DocumentStatusBadgeProps = {
    status: DocumentStatus;
    isOverdue?: boolean;
    className?: string;
};

export default function DocumentStatusBadge({
    status,
    isOverdue = false,
    className,
}: DocumentStatusBadgeProps) {
    if (isOverdue) {
        return (
            <Badge
                variant="outline"
                className={cn(
                    'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300',
                    className,
                )}
            >
                Overdue
            </Badge>
        );
    }

    return (
        <Badge
            variant="outline"
            className={cn(documentStatusClasses[status], className)}
        >
            {documentStatusLabels[status]}
        </Badge>
    );
}
