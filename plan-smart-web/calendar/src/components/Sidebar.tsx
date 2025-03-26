"use client"

import { useState, useEffect } from 'react';
import { Settings } from 'lucide-react';
import { useCalendarStore } from '@/store/useCalendarStore';
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Sheet, SheetContent, SheetHeader, SheetTitle } from "@/components/ui/sheet";
import { ScrollArea } from "@/components/ui/scroll-area";
import type { UserPreferences } from '@/types';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { notifications } from '@/lib/notifications';

export function Sidebar() {
  const { preferences, setPreferences, categories } = useCalendarStore();
  const [showPreferences, setShowPreferences] = useState(!preferences);
  const [localPreferences, setLocalPreferences] = useState<UserPreferences>({
    defaultEventDuration: 30,
    workingHours: {
      start: "09:00",
      end: "17:00",
    },
    breakDuration: 15,
    preferredMeetingDays: [1, 2, 3, 4, 5],
    minimumBreakBetweenEvents: 15,
    defaultView: 'week',
    quickEventTemplates: [],
  });

  useEffect(() => {
    if (preferences) {
      setLocalPreferences(preferences);
    }
  }, [preferences]);

  const handlePreferenceChange = (
    key: keyof UserPreferences,
    value: string | number | { start: string; end: string }
  ) => {
    let newValue = value;
    if (key === 'defaultEventDuration' || key === 'breakDuration' || key === 'minimumBreakBetweenEvents') {
      newValue = parseInt(value as string);
    }
    
    setLocalPreferences((prev) => ({
      ...prev,
      [key]: newValue,
    }));
  };

  const handleSavePreferences = () => {
    try {
      setPreferences(localPreferences);
      notifications.preferences.saved();
      if (!preferences) {
        setShowPreferences(false);
      }
    } catch (error) {
      notifications.preferences.error(error instanceof Error ? error.message : "Failed to save preferences");
    }
  };

  return (
    <div className="w-64 flex flex-col gap-4">
      <Card>
        <CardHeader>
          <CardTitle className="text-sm font-medium">Categories</CardTitle>
        </CardHeader>
        <CardContent className="p-0">
          <ScrollArea className="h-[200px]">
            <div className="p-4 space-y-2">
              {categories.map((category) => (
                <div
                  key={category.id}
                  className="flex items-center rounded-lg p-2 hover:bg-accent transition-colors cursor-pointer"
                >
                  <div
                    className="w-3 h-3 rounded-full mr-2"
                    style={{ backgroundColor: category.color }}
                  />
                  <span className="flex-1 text-sm">{category.name}</span>
                  {category.quickTemplates && category.quickTemplates.length > 0 && (
                    <span className="text-xs text-muted-foreground">
                      {category.quickTemplates.length} templates
                    </span>
                  )}
                </div>
              ))}
            </div>
          </ScrollArea>
        </CardContent>
      </Card>

      <Button
        variant="outline"
        className="w-full justify-start gap-2"
        onClick={() => setShowPreferences(true)}
      >
        <Settings className="h-4 w-4" />
        Preferences
      </Button>

      <Sheet open={showPreferences} onOpenChange={setShowPreferences}>
        <SheetContent side="left" className="w-[400px]">
          <SheetHeader>
            <SheetTitle>Preferences</SheetTitle>
          </SheetHeader>
          <ScrollArea className="h-[calc(100vh-8rem)]">
            <div className="space-y-6 py-6">
              <div className="space-y-2">
                <Label>Default View</Label>
                <Select
                  value={localPreferences.defaultView}
                  onValueChange={(value) => handlePreferenceChange('defaultView', value)}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select a view" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="day">Day</SelectItem>
                    <SelectItem value="week">Week</SelectItem>
                    <SelectItem value="month">Month</SelectItem>
                    <SelectItem value="year">Year</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label>Default Event Duration (min)</Label>
                <Input
                  type="number"
                  value={localPreferences.defaultEventDuration}
                  onChange={(e) => handlePreferenceChange('defaultEventDuration', e.target.value)}
                  min="5"
                  step="5"
                />
              </div>

              <div className="space-y-2">
                <Label>Working Hours</Label>
                <div className="grid grid-cols-2 gap-2">
                  <Input
                    type="time"
                    value={localPreferences.workingHours.start}
                    onChange={(e) =>
                      handlePreferenceChange('workingHours', {
                        ...localPreferences.workingHours,
                        start: e.target.value,
                      })
                    }
                  />
                  <Input
                    type="time"
                    value={localPreferences.workingHours.end}
                    onChange={(e) =>
                      handlePreferenceChange('workingHours', {
                        ...localPreferences.workingHours,
                        end: e.target.value,
                      })
                    }
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label>Break Duration (min)</Label>
                <Input
                  type="number"
                  value={localPreferences.breakDuration}
                  onChange={(e) => handlePreferenceChange('breakDuration', e.target.value)}
                  min="5"
                  step="5"
                />
              </div>

              <div className="space-y-2">
                <Label>Minimum Break Between Events (min)</Label>
                <Input
                  type="number"
                  value={localPreferences.minimumBreakBetweenEvents}
                  onChange={(e) => handlePreferenceChange('minimumBreakBetweenEvents', e.target.value)}
                  min="5"
                  step="5"
                />
              </div>

              <div className="space-y-2">
                <Label>Preferred Meeting Days</Label>
                <div className="grid grid-cols-2 gap-2">
                  {['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map((day, index) => (
                    <div key={day} className="flex items-center space-x-2">
                      <Checkbox
                        id={`day-${index}`}
                        checked={localPreferences.preferredMeetingDays.includes(index + 1)}
                        onCheckedChange={(checked) => {
                          const days = checked
                            ? [...localPreferences.preferredMeetingDays, index + 1]
                            : localPreferences.preferredMeetingDays.filter(d => d !== index + 1);
                          handlePreferenceChange('preferredMeetingDays', days);
                        }}
                      />
                      <Label htmlFor={`day-${index}`} className="text-sm">
                        {day}
                      </Label>
                    </div>
                  ))}
                </div>
              </div>

              <Button className="w-full" onClick={handleSavePreferences}>
                Save Preferences
              </Button>
            </div>
          </ScrollArea>
        </SheetContent>
      </Sheet>
    </div>
  );
}