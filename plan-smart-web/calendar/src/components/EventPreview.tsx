import {
  HoverCard,
  HoverCardContent,
  HoverCardTrigger,
} from "@/components/ui/hover-card";
import { CalendarEvent } from "@/types";
import { format, parseISO } from "date-fns";
import { CalendarDays, Clock, MapPin, Trash2 } from "lucide-react";

// Helper function to safely parse dates
const parseDate = (date: string | Date): Date => {
  if (date instanceof Date) return date;
  try {
    return parseISO(date);
  } catch (e) {
    return new Date();
  }
};

interface EventPreviewProps {
  event: CalendarEvent;
  children: React.ReactNode;
  onDelete?: (event: CalendarEvent) => void;
}

export function EventPreview({ event, children, onDelete }: EventPreviewProps) {
  const startDate = parseDate(event.start);
  const endDate = parseDate(event.end);

  return (
    <HoverCard>
      <HoverCardTrigger asChild>
        {children}
      </HoverCardTrigger>
      <HoverCardContent className="w-80">
        <div className="flex flex-col space-y-2">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <div
                className="w-3 h-3 rounded-full"
                style={{ backgroundColor: event.category.color }}
              />
              <span className="font-medium">{event.title}</span>
            </div>
            {onDelete && (
              <button
                onClick={(e) => {
                  e.preventDefault();
                  e.stopPropagation();
                  onDelete(event);
                }}
                className="p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400"
                aria-label="Delete event"
              >
                <Trash2 className="h-4 w-4" />
              </button>
            )}
          </div>
          
          {event.description && (
            <p className="text-sm text-muted-foreground line-clamp-2">
              {event.description}
            </p>
          )}
          <div className="flex items-center gap-2 text-sm text-muted-foreground">
            <CalendarDays className="h-4 w-4" />
            <span>{format(startDate, "EEEE, MMMM d, yyyy")}</span>
          </div>
          <div className="flex items-center gap-2 text-sm text-muted-foreground">
            <Clock className="h-4 w-4" />
            <span>
              {format(startDate, "h:mm a")} - {format(endDate, "h:mm a")}
            </span>
          </div>
          {event.location && (
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <MapPin className="h-4 w-4" />
              <span>{event.location}</span>
            </div>
          )}
          <div className="text-xs text-muted-foreground mt-2">
            {event.category.name}
          </div>
        </div>
      </HoverCardContent>
    </HoverCard>
  );
}