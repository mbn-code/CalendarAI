import { useCallback, useState } from "react";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "./ui/dialog";
import { Input } from "./ui/input";
import { Label } from "./ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "./ui/select";
import { Textarea } from "./ui/textarea";
import { Button } from "./ui/button";
import type { AIPreferences } from "@/types/ai";

interface AIPreferencesDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  preferences: AIPreferences;
  onPreferencesChange: (prefs: AIPreferences) => void;
  viewMode: 'day' | 'week' | 'month';
}

const defaultSystemPrompt = `I am your personal calendar assistant. When optimizing schedules:
- I prefer to work from {preferredStartTime} to {preferredEndTime}
- I am most focused in the {focusTimePreference}
- I need {preferredBreakDuration} minute breaks between tasks
- My ideal work session is {preferredSessionDuration} minutes
- I prefer no more than {maximumMeetingsPerDay} meetings per day
- My ideal meeting length is {preferredMeetingDuration} minutes
Please help me maintain a balanced and productive schedule while respecting these preferences.`;

export function AIPreferencesDialog({
  open,
  onOpenChange,
  preferences,
  onPreferencesChange,
  viewMode,
}: AIPreferencesDialogProps) {
  const [optimizing, setOptimizing] = useState(false);

  const handleChange = useCallback(
    (field: keyof AIPreferences, value: any) => {
      onPreferencesChange({
        ...preferences,
        [field]: value,
      });
    },
    [preferences, onPreferencesChange]
  );

  const getOptimizationDescription = () => {
    switch (viewMode) {
      case 'day':
        return "Optimize your daily schedule for maximum focus and productivity. AI will help arrange your tasks for optimal energy levels and minimize context switching.";
      case 'week':
        return "Balance your weekly schedule by distributing workload evenly, ensuring adequate breaks, and grouping similar tasks together.";
      case 'month':
        return "Get a high-level review of your monthly commitments, identify potential conflicts, and suggest better time allocation patterns.";
    }
  };

  const handleOptimize = async () => {
    setOptimizing(true);
    try {
      // Optimization logic will be implemented here
      await new Promise(resolve => setTimeout(resolve, 1500)); // Temporary simulation
    } finally {
      setOptimizing(false);
      onOpenChange(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl">
        <DialogHeader>
          <DialogTitle>AI Schedule Optimizer Preferences</DialogTitle>
          <DialogDescription>
            {getOptimizationDescription()}
          </DialogDescription>
        </DialogHeader>
        <div className="grid gap-6 py-4">
          {viewMode === 'day' && (
            <div className="space-y-2">
              <h3 className="font-medium">Daily Focus Settings</h3>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Preferred Start Time</Label>
                  <Input
                    type="time"
                    value={preferences.preferredStartTime}
                    onChange={(e) => handleChange("preferredStartTime", e.target.value)}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Preferred End Time</Label>
                  <Input
                    type="time"
                    value={preferences.preferredEndTime}
                    onChange={(e) => handleChange("preferredEndTime", e.target.value)}
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label>Focus Time Preference</Label>
                <Select
                  value={preferences.focusTimePreference}
                  onValueChange={(value) =>
                    handleChange("focusTimePreference", value as AIPreferences["focusTimePreference"])
                  }
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="morning">Morning</SelectItem>
                    <SelectItem value="afternoon">Afternoon</SelectItem>
                    <SelectItem value="evening">Evening</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Break Duration (minutes)</Label>
                  <Input
                    type="number"
                    min={5}
                    max={60}
                    value={preferences.preferredBreakDuration}
                    onChange={(e) =>
                      handleChange("preferredBreakDuration", parseInt(e.target.value))
                    }
                  />
                </div>
                <div className="space-y-2">
                  <Label>Session Duration (minutes)</Label>
                  <Input
                    type="number"
                    min={15}
                    max={180}
                    value={preferences.preferredSessionDuration}
                    onChange={(e) =>
                      handleChange("preferredSessionDuration", parseInt(e.target.value))
                    }
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Maximum Meetings Per Day</Label>
                  <Input
                    type="number"
                    min={1}
                    max={10}
                    value={preferences.maximumMeetingsPerDay}
                    onChange={(e) =>
                      handleChange("maximumMeetingsPerDay", parseInt(e.target.value))
                    }
                  />
                </div>
                <div className="space-y-2">
                  <Label>Preferred Meeting Length (minutes)</Label>
                  <Input
                    type="number"
                    min={15}
                    max={180}
                    step={15}
                    value={preferences.preferredMeetingDuration}
                    onChange={(e) =>
                      handleChange("preferredMeetingDuration", parseInt(e.target.value))
                    }
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label>AI Assistant Personality</Label>
                <Textarea
                  value={preferences.systemPrompt}
                  onChange={(e) => handleChange("systemPrompt", e.target.value)}
                  placeholder="Describe how you want the AI to assist you..."
                  rows={6}
                />
                <p className="text-sm text-gray-500">
                  This prompt helps the AI understand your preferences and personality. You can modify it to better suit your needs.
                </p>
              </div>

              <Button
                variant="default"
                onClick={() => {
                  handleChange("systemPrompt", defaultSystemPrompt);
                }}
              >
                Reset to Default Prompt
              </Button>
            </div>
          )}
          {viewMode === 'week' && (
            <div className="space-y-2">
              <h3 className="font-medium">Weekly Balance Settings</h3>
              {/* Weekly-specific settings will go here */}
            </div>
          )}
          {viewMode === 'month' && (
            <div className="space-y-2">
              <h3 className="font-medium">Monthly Overview Settings</h3>
              {/* Monthly-specific settings will go here */}
            </div>
          )}
        </div>
        <div className="flex justify-end gap-3">
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancel
          </Button>
          <Button onClick={handleOptimize} disabled={optimizing}>
            {optimizing ? "Optimizing..." : "Run AI Optimization"}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}