"use client"

import { useState, useEffect } from 'react';
import { X, Clock, Calendar, MapPin, Tag } from 'lucide-react';
import { useCalendarStore } from '@/store/useCalendarStore';
import type { CalendarEvent, EventCategory } from '@/types';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { format } from "date-fns";
import { notifications } from '@/lib/notifications';

const DEFAULT_CATEGORY: EventCategory = {
  id: 'default',
  name: 'Default',
  color: '#808080',
  quickTemplates: [],
};

interface EventDialogProps {
  isOpen: boolean;
  onClose: () => void;
  selectedDate: Date | null;
  event?: CalendarEvent;
}

export function EventDialog({ isOpen, onClose, selectedDate, event }: EventDialogProps) {
  const { preferences, categories, addEvent, updateEvent } = useCalendarStore();
  const [formData, setFormData] = useState<Omit<CalendarEvent, 'id'>>({
    title: '',
    description: '',
    start: new Date(),
    end: new Date(),
    category: categories[0] || DEFAULT_CATEGORY,
    location: '',
  });

  useEffect(() => {
    if (selectedDate) {
      const start = new Date(selectedDate);
      const end = new Date(selectedDate);
      end.setMinutes(end.getMinutes() + preferences.defaultEventDuration);
      setFormData(prev => ({ ...prev, start, end }));
    }
  }, [selectedDate, preferences.defaultEventDuration]);

  useEffect(() => {
    if (event) {
      setFormData({
        title: event.title,
        description: event.description || '',
        start: event.start,
        end: event.end,
        category: event.category,
        location: event.location || '',
      });
    }
  }, [event]);

  const quickTemplates = [
    { title: '15min Meeting', duration: 15 },
    { title: '30min Meeting', duration: 30 },
    { title: '1h Meeting', duration: 60 },
    ...preferences.quickEventTemplates,
  ];

  const handleQuickTemplate = (template: { title: string; duration: number }) => {
    const end = new Date(formData.start);
    end.setMinutes(end.getMinutes() + template.duration);
    setFormData(prev => ({
      ...prev,
      title: template.title,
      end,
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    try {
      if (!formData.title) {
        notifications.event.error("Event title is required");
        return;
      }

      if (event) {
        // Edit existing event
        updateEvent(event.id, formData);
        notifications.event.updated(formData.title);
      } else {
        // Create new event
        addEvent(formData);
        notifications.event.created(formData.title);
      }
      onClose();
    } catch (error) {
      notifications.event.error(error instanceof Error ? error.message : "Failed to save event");
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>
            {event ? 'Edit Event' : 'New Event'}
          </DialogTitle>
        </DialogHeader>
        
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Input
              placeholder="Add title"
              className="text-lg font-medium border-0 px-0 h-auto"
              value={formData.title}
              onChange={e => setFormData(prev => ({ ...prev, title: e.target.value }))}
              autoFocus
            />
            
            {!formData.title && (
              <div className="flex flex-wrap gap-2 pb-2">
                {quickTemplates.map((template, index) => (
                  <Button
                    key={index}
                    type="button"
                    variant="secondary"
                    size="sm"
                    onClick={() => handleQuickTemplate(template)}
                  >
                    {template.title}
                  </Button>
                ))}
              </div>
            )}
          </div>

          <div className="grid gap-2">
            <Label>Time</Label>
            <div className="flex items-center gap-2">
              <Input
                type="datetime-local"
                value={format(formData.start, "yyyy-MM-dd'T'HH:mm")}
                onChange={e => setFormData(prev => ({ ...prev, start: new Date(e.target.value) }))}
                className="flex-1"
              />
              <span className="text-muted-foreground">to</span>
              <Input
                type="datetime-local"
                value={format(formData.end, "yyyy-MM-dd'T'HH:mm")}
                onChange={e => setFormData(prev => ({ ...prev, end: new Date(e.target.value) }))}
                className="flex-1"
              />
            </div>
          </div>

          <div className="grid gap-2">
            <Label>Location</Label>
            <div className="flex items-center gap-2">
              <MapPin className="w-4 h-4 text-muted-foreground" />
              <Input
                placeholder="Add location"
                value={formData.location}
                onChange={e => setFormData(prev => ({ ...prev, location: e.target.value }))}
              />
            </div>
          </div>

          <div className="grid gap-2">
            <Label>Category</Label>
            <div className="grid grid-cols-4 gap-2">
              {categories.map(category => (
                <Button
                  key={category.id}
                  type="button"
                  variant="outline"
                  className={`h-auto py-2 px-3 ${
                    formData.category.id === category.id ? 'ring-2 ring-ring' : ''
                  }`}
                  onClick={() => setFormData(prev => ({ ...prev, category }))}
                >
                  <div
                    className="w-4 h-4 rounded-full mx-auto mb-1"
                    style={{ backgroundColor: category.color }}
                  />
                  <span className="text-xs font-medium">{category.name}</span>
                </Button>
              ))}
            </div>
          </div>

          <div className="grid gap-2">
            <Label>Description</Label>
            <Textarea
              placeholder="Add description"
              value={formData.description}
              onChange={e => setFormData(prev => ({ ...prev, description: e.target.value }))}
              rows={3}
            />
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={onClose}>
              Cancel
            </Button>
            <Button type="submit">
              {event ? 'Save' : 'Create'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}