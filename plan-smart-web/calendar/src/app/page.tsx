"use client";

import { useState } from "react";
import { Calendar } from "@/components/Calendar";
import { Button } from "@/components/ui/button";
import { AIPreferencesDialog } from "@/components/AIPreferencesDialog";
import { Wand2 } from "lucide-react";
import type { AIPreferences } from "@/types/ai";

const defaultAIPreferences: AIPreferences = {
  preferredStartTime: "09:00",
  preferredEndTime: "17:00",
  focusTimePreference: "morning",
  preferredBreakDuration: 15,
  preferredSessionDuration: 50,
  maximumMeetingsPerDay: 4,
  preferredMeetingDuration: 30,
  breaksBetweenTasks: true,
  systemPrompt: "I am your personal calendar assistant..."
};

export default function HomePage() {
  const [viewMode, setViewMode] = useState<'day' | 'week' | 'month'>('week');
  const [showAIPreferences, setShowAIPreferences] = useState(false);
  const [aiPreferences, setAIPreferences] = useState<AIPreferences>(defaultAIPreferences);

  return (
    <main className="container mx-auto p-4 space-y-4">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold">Calendar</h1>
        <Button 
          onClick={() => setShowAIPreferences(true)}
          className="gap-2"
          variant="outline"
        >
          <Wand2 className="h-4 w-4" />
          AI Optimize {viewMode.charAt(0).toUpperCase() + viewMode.slice(1)}
        </Button>
      </div>
      
      <Calendar 
        viewMode={viewMode}
        onViewModeChange={setViewMode}
      />

      <AIPreferencesDialog
        open={showAIPreferences}
        onOpenChange={setShowAIPreferences}
        preferences={aiPreferences}
        onPreferencesChange={setAIPreferences}
        viewMode={viewMode}
      />
    </main>
  );
}
