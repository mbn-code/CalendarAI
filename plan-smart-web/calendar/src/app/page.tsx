import Link from "next/link";
import { Calendar } from "@/components/Calendar";
import { Sidebar } from "@/components/Sidebar";

export default function HomePage() {
  return (
    <div className="flex h-screen">
      <Sidebar />
      <main className="flex-1 overflow-auto p-4">
        <div className="mx-auto max-w-7xl">
          <h1 className="mb-4 text-2xl font-bold">Your Calendar</h1>
          <Calendar />
        </div>
      </main>
    </div>
  );
}
