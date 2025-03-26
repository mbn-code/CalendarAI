import { NextResponse } from 'next/server';
import axios from 'axios';

type OllamaRequest = {
  model: string;
  prompt: string;
  system?: string;
  context?: number[];
};

type Suggestion = {
  type: 'move' | 'split' | 'break';
  description: string;
  changes: {
    eventId?: string;
    newStartTime?: string;
    newEndTime?: string;
    newDate?: string;
  };
};

export async function POST(request: Request) {
  try {
    const body = await request.json();
    const { events, preferences, systemPrompt } = body;

    const prompt = `Given these calendar events:
${JSON.stringify(events, null, 2)}

And these user preferences:
${JSON.stringify(preferences, null, 2)}

Analyze the schedule and provide optimization suggestions. Consider:
1. Time conflicts
2. Meeting density
3. Focus time preferences
4. Break scheduling
5. Work-life balance

Return your response in this exact JSON format:
{
  "suggestions": [
    {
      "type": "move|split|break",
      "description": "Description of the suggestion",
      "changes": {
        "eventId": "id of event to change",
        "newStartTime": "new start time if moving",
        "newEndTime": "new end time if moving",
        "newDate": "new date if moving to different day"
      }
    }
  ]
}`;

    const response = await axios.post('http://localhost:11434/api/generate', {
      model: 'mistral',
      prompt,
      system: systemPrompt,
      format: 'json',
      stream: false
    });

    // Extract the generated text from Ollama's response
    let suggestions: Suggestion[] = [];
    try {
      const generatedText = response.data.response;
      const parsedResponse = JSON.parse(generatedText);
      suggestions = parsedResponse.suggestions || [];
    } catch (parseError) {
      console.error('Failed to parse AI response:', parseError);
      suggestions = [];
    }

    return NextResponse.json({ suggestions }, { status: 200 });
  } catch (error) {
    console.error('Ollama API error:', error);
    return NextResponse.json(
      { error: 'Failed to get AI suggestions. Make sure Ollama is running locally.' },
      { status: 500 }
    );
  }
}