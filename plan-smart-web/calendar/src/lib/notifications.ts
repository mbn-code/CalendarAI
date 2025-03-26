import { toast } from 'sonner';

export const notifications = {
  event: {
    created: (title: string) =>
      toast.success('Event Created', {
        description: `Successfully created event "${title}"`,
      }),
    updated: (title: string) =>
      toast.success('Event Updated', {
        description: `Successfully updated event "${title}"`,
      }),
    deleted: (title: string) =>
      toast.success('Event Deleted', {
        description: `Successfully deleted event "${title}"`,
      }),
    error: (message: string) =>
      toast.error('Error', {
        description: message,
      }),
  },
  preferences: {
    saved: () =>
      toast.success('Preferences Saved', {
        description: 'Your preferences have been updated successfully.',
      }),
    error: (message: string) =>
      toast.error('Error', {
        description: `Failed to save preferences: ${message}`,
      }),
  },
};