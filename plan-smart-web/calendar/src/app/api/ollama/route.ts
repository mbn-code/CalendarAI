import { NextResponse } from "next/server";
import { AIPreferences, OptimizerResponse } from "@/types/ai";

export async function POST(request: Request) {
  try {
    const body = await request.json();
    const { events, preferences, systemPrompt } = body as {
      events: any[];
      preferences: AIPreferences;
      systemPrompt: string;
    };

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

    const response = await fetch("http://localhost:11434/api/generate", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        model: "llama2",
        prompt,
        stream: false,
      }),
    });

    if (!response.ok) {
      throw new Error("Failed to get AI suggestions");
    }

    const data = await response.json();
    let suggestions;
    
    try {
      suggestions = JSON.parse(data.response);
    } catch (e) {
      console.error("Failed to parse AI response:", e);
      suggestions = { suggestions: [] };
    }

    return NextResponse.json(suggestions);
  } catch (error) {
    console.error("Failed to optimize schedule:", error);
    return NextResponse.json(
      { error: "Failed to optimize schedule" },
      { status: 500 }
    );
  }
}