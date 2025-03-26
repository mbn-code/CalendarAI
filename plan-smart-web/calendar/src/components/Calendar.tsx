"use client"

import { useState, useEffect, useCallback } from 'react';
import { ChevronLeft, ChevronRight, Plus, Calendar as CalendarIcon } from 'lucide-react';
import type { ViewMode, CalendarEvent, UserPreferences } from '@/types';
import { useCalendarStore } from '@/store/useCalendarStore';
import { Button } from "@/components/ui/button";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { DayView, WeekView, MonthView, YearView } from './CalendarViews';
import { EventDialog } from './EventDialog';
import { QuickCreatePopover } from './QuickCreatePopover';
import { format } from 'date-fns';

const viewModeOptions: { value: ViewMode; label: string }[] = [
  { value: 'day', label: 'Day' },
  { value: 'week', label: 'Week' },
  { value: 'month', label: 'Month' },
  { value: 'year', label: 'Year' },
];

export function Calendar() {
  const { preferences } = useCalendarStore();
  const [viewMode, setViewMode] = useState<ViewMode>(preferences?.defaultView || 'week');
  const [currentDate, setCurrentDate] = useState(new Date());
  const [selectedDate, setSelectedDate] = useState<Date | null>(null);
  const [showEventDialog, setShowEventDialog] = useState(false);
  const [quickCreateAnchor, setQuickCreateAnchor] = useState<{ x: number; y: number } | null>(null);

  const navigateDate = useCallback((direction: 'prev' | 'next') => {
    setCurrentDate(prevDate => {
      const newDate = new Date(prevDate);
      switch (viewMode) {
        case 'day':
          newDate.setDate(prevDate.getDate() + (direction === 'next' ? 1 : -1));
          break;
        case 'week':
          newDate.setDate(prevDate.getDate() + (direction === 'next' ? 7 : -7));
          break;
        case 'month':
          newDate.setMonth(prevDate.getMonth() + (direction === 'next' ? 1 : -1));
          break;
        case 'year':
          newDate.setFullYear(prevDate.getFullYear() + (direction === 'next' ? 1 : -1));
          break;
      }
      return newDate;
    });
  }, [viewMode]);

  const handleKeyPress = useCallback((e: KeyboardEvent) => {
    if (e.key === 'c' && (e.metaKey || e.ctrlKey)) {
      e.preventDefault();
      setShowEventDialog(true);
    }
  }, []);

  useEffect(() => {
    window.addEventListener('keydown', handleKeyPress);
    return () => window.removeEventListener('keydown', handleKeyPress);
  }, [handleKeyPress]);

  const handleQuickCreate = (e: React.MouseEvent) => {
    if (e.altKey) {
      e.preventDefault();
      setQuickCreateAnchor({ x: e.clientX, y: e.clientY });
    }
  };

  const handleDateClick = (date: Date) => {
    setSelectedDate(date);
    setShowEventDialog(true);
  };

  const renderViewModeContent = () => {
    switch (viewMode) {
      case 'day':
        return <DayView date={currentDate} onDateClick={handleDateClick} />;
      case 'week':
        return <WeekView date={currentDate} onDateClick={handleDateClick} />;
      case 'month':
        return <MonthView date={currentDate} onDateClick={handleDateClick} />;
      case 'year':
        return <YearView date={currentDate} onDateClick={handleDateClick} />;
    }
  };

  return (
    <div className="h-full flex flex-col" onMouseDown={handleQuickCreate}>
      <Card>
        <CardHeader className="pb-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-4">
              <div className="flex items-center space-x-2">
                <Button
                  variant="outline"
                  size="icon"
                  onClick={() => navigateDate('prev')}
                >
                  <ChevronLeft className="h-4 w-4" />
                </Button>
                <Button
                  variant="outline"
                  size="icon"
                  onClick={() => navigateDate('next')}
                >
                  <ChevronRight className="h-4 w-4" />
                </Button>
                <Button
                  variant="outline"
                  onClick={() => setCurrentDate(new Date())}
                >
                  Today
                </Button>
              </div>
              <CardTitle className="text-xl">
                {format(currentDate, viewMode === 'day' ? 'MMMM d, yyyy' : 'MMMM yyyy')}
              </CardTitle>
            </div>

            <div className="flex items-center space-x-2">
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="outline" className="gap-2">
                    <CalendarIcon className="h-4 w-4" />
                    {viewModeOptions.find(v => v.value === viewMode)?.label}
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent>
                  {viewModeOptions.map((option) => (
                    <DropdownMenuItem
                      key={option.value}
                      onClick={() => setViewMode(option.value)}
                    >
                      {option.label}
                    </DropdownMenuItem>
                  ))}
                </DropdownMenuContent>
              </DropdownMenu>
              <Button onClick={() => setShowEventDialog(true)} className="gap-2">
                <Plus className="h-4 w-4" />
                Event
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent className="pb-4">
          <div className="flex-1 overflow-auto">
            {renderViewModeContent()}
          </div>
        </CardContent>
      </Card>

      {showEventDialog && (
        <EventDialog
          isOpen={showEventDialog}
          onClose={() => setShowEventDialog(false)}
          selectedDate={selectedDate}
        />
      )}

      {quickCreateAnchor && (
        <QuickCreatePopover
          position={quickCreateAnchor}
          onClose={() => setQuickCreateAnchor(null)}
          date={currentDate}
        />
      )}
    </div>
  );
}