import type { NextRequest } from 'next/server';
import { NextResponse } from 'next/server';
import type { Event } from '@/lib/utils';
import { v4 as uuidv4 } from 'uuid';

const OLLAMA_API_HOST = 'http://localhost:11434';

interface OllamaResponse {
  model: string;
  created_at: string;
  response: string;
  done: boolean;
  context: number[];
  total_duration: number;
  load_duration: number;
  prompt_eval_count: number;
  prompt_eval_duration: number;
  eval_count: number;
  eval_duration: number;
}

async function generateOptimizedSchedule(prompt: string, systemPreferences?: string): Promise<Event[]> {
  const systemPrompt = `You are a smart calendar assistant that helps optimize schedules. 
  Given the user's preferences and constraints, create an optimized daily schedule that includes:
  - Work/study blocks with appropriate breaks
  - Exercise/gym time
  - Meal times
  - Personal time
  Consider energy levels throughout the day and the user's stated preferences.
  ${systemPreferences ? `\nUser's default preferences: ${systemPreferences}` : ''}
  
  Respond with a JSON array of events that follows this exact format (use UTC dates):
  [
    {
      "id": "uuid string",
      "title": "string",
      "start": "2024-XX-XXTXX:XX:XX.XXXZ",
      "end": "2024-XX-XXTXX:XX:XX.XXXZ",
      "type": "work" | "study" | "exercise" | "break" | "other"
    }
  ]`;

  try {
    const response = await fetch(`${OLLAMA_API_HOST}/api/generate`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        model: 'mistral',
        prompt: `${systemPrompt}\n\nUser request: ${prompt}\n\nGenerate an optimized schedule:`,
        stream: false,
      }),
    });

    if (!response.ok) {
      throw new Error('Failed to connect to Ollama');
    }

    const data = (await response.json()) as OllamaResponse;
    const schedule = JSON.parse(data.response) as Array<Omit<Event, 'id'>>;
    
    // Ensure each event has a UUID
    return schedule.map((event) => ({
      ...event,
      id: uuidv4(),
    }));
  } catch (error) {
    console.error('Error calling Ollama:', error);
    throw error;
  }
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json() as { prompt: string; systemPreferences?: string };
    const optimizedSchedule = await generateOptimizedSchedule(body.prompt, body.systemPreferences);
    return NextResponse.json(optimizedSchedule);
  } catch (error) {
    console.error('Error in optimize route:', error);
    return NextResponse.json(
      { error: 'Failed to optimize schedule' },
      { status: 500 }
    );
  }
}