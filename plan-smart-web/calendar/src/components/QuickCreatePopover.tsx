import { useState } from 'react';
import { useCalendarStore } from '@/store/useCalendarStore';
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { ScrollArea } from "@/components/ui/scroll-area";

interface QuickCreatePopoverProps {
  position: { x: number; y: number };
  onClose: () => void;
  date: Date;
}

export function QuickCreatePopover({ position, onClose, date }: QuickCreatePopoverProps) {
  const { preferences, categories } = useCalendarStore();
  const [title, setTitle] = useState('');

  const handleSubmit = (duration: number) => {
    // TODO: Create event using useCalendarStore
    onClose();
  };

  // Create an invisible trigger that we can programmatically position
  const triggerStyle: React.CSSProperties = {
    position: 'fixed',
    left: `${position.x}px`,
    top: `${position.y}px`,
    width: '1px',
    height: '1px',
    padding: 0,
    margin: 0,
    border: 'none',
    background: 'transparent',
  };

  return (
    <Popover open={true} onOpenChange={onClose}>
      <PopoverTrigger asChild>
        <button style={triggerStyle} />
      </PopoverTrigger>
      <PopoverContent className="w-64" side="right" align="start">
        <div className="space-y-2">
          <Input
            type="text"
            placeholder="Quick add event..."
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            className="w-full"
            autoFocus
          />
          <ScrollArea className="h-[120px]">
            <div className="space-y-1">
              <Button
                variant="ghost"
                className="w-full justify-start font-normal"
                onClick={() => handleSubmit(30)}
              >
                30 minutes
              </Button>
              <Button
                variant="ghost"
                className="w-full justify-start font-normal"
                onClick={() => handleSubmit(60)}
              >
                1 hour
              </Button>
              <Button
                variant="ghost"
                className="w-full justify-start font-normal"
                onClick={() => handleSubmit(preferences.defaultEventDuration)}
              >
                {preferences.defaultEventDuration} minutes (default)
              </Button>
              {categories.map((category) => (
                category.quickTemplates?.map((template) => (
                  <Button
                    key={`${category.id}-${template.title}`}
                    variant="ghost"
                    className="w-full justify-start font-normal"
                    onClick={() => handleSubmit(template.duration)}
                  >
                    {template.title} ({template.duration}min)
                  </Button>
                ))
              ))}
            </div>
          </ScrollArea>
        </div>
      </PopoverContent>
    </Popover>
  );
}