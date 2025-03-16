import { useState } from 'react';
import type { Event } from '@/lib/utils';

export function useOptimizeSchedule() {
  const [isOptimizing, setIsOptimizing] = useState(false);

  const optimizeSchedule = async (userPrompt: string) => {
    try {
      setIsOptimizing(true);
      
      // Get system preferences from localStorage
      const systemPreferences = localStorage.getItem('system-prompt');

      const response = await fetch('/api/optimize', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
          prompt: userPrompt,
          systemPreferences 
        }),
      });

      if (!response.ok) {
        throw new Error('Failed to optimize schedule');
      }

      const optimizedEvents = (await response.json()) as Event[];
      return optimizedEvents;
    } catch (error) {
      console.error('Error optimizing schedule:', error);
      throw error;
    } finally {
      setIsOptimizing(false);
    }
  };

  return {
    optimizeSchedule,
    isOptimizing,
  };
}