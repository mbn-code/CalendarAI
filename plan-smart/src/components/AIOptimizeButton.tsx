"use client";

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { useState, useEffect } from 'react';
import { Wand2, Settings } from 'lucide-react';
import { useOptimizeSchedule } from '@/lib/hooks/useOptimizeSchedule';
import { useToast } from '@/components/ui/toast-context';
import type { Event } from '@/lib/utils';
import Link from 'next/link';

interface AIOptimizeButtonProps {
  onEventsUpdate: (events: Event[]) => void;
  currentEvents: Event[];
}

export function AIOptimizeButton({ onEventsUpdate, currentEvents }: AIOptimizeButtonProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [userPrompt, setUserPrompt] = useState('');
  const [hasPreferences, setHasPreferences] = useState(false);
  const { optimizeSchedule, isOptimizing } = useOptimizeSchedule();
  const { showToast } = useToast();

  useEffect(() => {
    const preferences = localStorage.getItem('system-prompt');
    setHasPreferences(!!preferences);
  }, []);

  const handleOptimize = async () => {
    try {
      if (!userPrompt.trim()) {
        showToast('Error', 'Please provide your scheduling request');
        return;
      }

      const optimizedEvents = await optimizeSchedule(userPrompt);
      onEventsUpdate([...currentEvents, ...optimizedEvents]);
      setIsOpen(false);
      setUserPrompt('');
      showToast(
        'Schedule Optimized',
        'Your calendar has been updated with AI-optimized events'
      );
    } catch (error) {
      console.error('Failed to optimize schedule:', error);
      showToast(
        'Optimization Failed',
        'There was an error optimizing your schedule. Please try again.'
      );
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={setIsOpen}>
      <DialogTrigger asChild>
        <Button className="gap-2 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700">
          <Wand2 className="h-4 w-4" />
          Optimize Schedule
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>AI Schedule Optimization</DialogTitle>
          <DialogDescription asChild>
            <div className="space-y-2">
              <span>Tell me what you&apos;d like to accomplish, and I&apos;ll optimize your schedule accordingly.</span>
              {!hasPreferences && (
                <div className="flex items-center gap-2 text-yellow-600">
                  <Settings className="h-4 w-4" />
                  <span>
                    Tip: Set up your{' '}
                    <Link href="/settings" className="underline hover:text-yellow-700">
                      default preferences
                    </Link>
                    {' '}for better optimization
                  </span>
                </div>
              )}
            </div>
          </DialogDescription>
        </DialogHeader>
        <div className="grid gap-4 py-4">
          <Textarea
            placeholder="Example: I need to schedule 4 hours of focused work, a 1-hour workout, and want to make sure I take regular breaks..."
            value={userPrompt}
            onChange={(e) => setUserPrompt(e.target.value)}
            rows={6}
            className="resize-none"
          />
          <Button 
            onClick={handleOptimize} 
            disabled={isOptimizing}
            className="w-full bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700"
          >
            {isOptimizing ? (
              <span className="flex items-center gap-2">
                <div className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                Optimizing...
              </span>
            ) : (
              'Optimize My Schedule'
            )}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}