"use client";

import { useState, useEffect } from 'react';
import { Card } from '@tremor/react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/components/ui/toast-context';

export default function SettingsPage() {
  const [systemPrompt, setSystemPrompt] = useState('');
  const { showToast } = useToast();
  
  // Load saved preferences on mount
  useEffect(() => {
    const savedPrompt = localStorage.getItem('system-prompt');
    if (savedPrompt) {
      setSystemPrompt(savedPrompt);
    }
  }, []);

  const handleSave = () => {
    try {
      localStorage.setItem('system-prompt', systemPrompt);
      showToast('Settings Saved', 'Your AI preferences have been updated');
    } catch (error) {
      console.error('Error saving settings:', error);
      showToast('Error', 'Failed to save settings');
    }
  };

  return (
    <main className="min-h-screen bg-gradient-to-b from-gray-50 to-white">
      <div className="container mx-auto px-4 py-8">
        <h1 className="text-4xl font-bold tracking-tight bg-gradient-to-r from-indigo-500 to-purple-600 bg-clip-text text-transparent mb-8">
          AI Settings
        </h1>

        <Card className="p-6 space-y-6">
          <div>
            <h2 className="text-xl font-semibold mb-2">Default Scheduling Preferences</h2>
            <p className="text-gray-600 mb-4">
              Configure your default scheduling preferences and constraints. This will help the AI better understand how you prefer to organize your day.
            </p>
            <Textarea
              value={systemPrompt}
              onChange={(e) => setSystemPrompt(e.target.value)}
              placeholder="Example: I'm most productive in the mornings, prefer exercise sessions before noon, need regular breaks every 2 hours, and like to have lunch around 1 PM..."
              rows={8}
              className="mb-4"
            />
            <Button 
              onClick={handleSave}
              className="bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700"
            >
              Save Preferences
            </Button>
          </div>
        </Card>
      </div>
    </main>
  );
}