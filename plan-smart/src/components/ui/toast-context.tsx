"use client";

import React, { createContext, useContext, useState } from 'react';
import { Toast, ToastDescription, ToastProvider as ToastProviderPrimitive, ToastTitle, ToastViewport } from './toast';

interface ToastContextType {
  showToast: (title: string, description: string) => void;
}

const ToastContext = createContext<ToastContextType | undefined>(undefined);

export function ToastProvider({ children }: { children: React.ReactNode }) {
  const [toasts, setToasts] = useState<Array<{ id: string; title: string; description: string }>>([]);

  const showToast = (title: string, description: string) => {
    const id = Math.random().toString();
    setToasts((prev) => [...prev, { id, title, description }]);
    setTimeout(() => {
      setToasts((prev) => prev.filter((toast) => toast.id !== id));
    }, 5000);
  };

  return (
    <ToastContext.Provider value={{ showToast }}>
      <ToastProviderPrimitive>
        {children}
        {toasts.map((toast) => (
          <Toast key={toast.id}>
            <div>
              <ToastTitle>{toast.title}</ToastTitle>
              <ToastDescription>{toast.description}</ToastDescription>
            </div>
          </Toast>
        ))}
        <ToastViewport />
      </ToastProviderPrimitive>
    </ToastContext.Provider>
  );
}

export function useToast() {
  const context = useContext(ToastContext);
  if (!context) {
    throw new Error('useToast must be used within a ToastProvider');
  }
  return context;
}