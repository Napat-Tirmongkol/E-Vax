import { useNavigate } from "react-router";
import { useState } from "react";
import { ChevronLeft, ChevronRight, Calendar as CalendarIcon } from "lucide-react";
import { cn } from "../lib/utils";

// Mock calendar for March 2026
const MARCH_2026_DAYS = 31;
const START_DAY_OF_WEEK = 0; // Sunday

type Density = 'high' | 'medium' | 'low' | 'disabled';
const mockDensity = (day: number): Density => {
  if (day < 12) return 'disabled'; // past dates
  if ([14, 15, 21, 22, 28, 29].includes(day)) return 'high'; // Weekends - fully booked/high density
  if ([12, 13, 16, 17, 23, 24].includes(day)) return 'medium'; // Some days medium
  return 'low'; // others available (green)
};

const DENSITY_COLORS = {
  high: "bg-red-500",
  medium: "bg-yellow-400",
  low: "bg-green-500",
  disabled: "bg-gray-200"
};

export function BookingDatePage() {
  const navigate = useNavigate();
  const [selectedDate, setSelectedDate] = useState<number | null>(12);

  const days = Array.from({ length: MARCH_2026_DAYS }, (_, i) => i + 1);
  const blanks = Array.from({ length: START_DAY_OF_WEEK }, (_, i) => i);
  const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

  return (
    <div className="p-5 flex flex-col h-full animate-in fade-in slide-in-from-right-4 duration-500">
      <div className="flex-1">
        <div className="flex items-center gap-2 mb-5">
          <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
            <CalendarIcon className="w-5 h-5 text-[#0052CC]" />
          </div>
          <div>
            <h2 className="text-xl font-bold text-gray-900">Select Date</h2>
            <p className="text-xs text-gray-500">Choose your vaccination day</p>
          </div>
        </div>

        {/* Calendar Card */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
          <div className="flex items-center justify-between mb-5">
            <button className="p-2 hover:bg-gray-100 rounded-lg text-gray-500 transition-colors"><ChevronLeft className="w-5 h-5" /></button>
            <h3 className="font-bold text-gray-800 text-lg">March 2026</h3>
            <button className="p-2 hover:bg-gray-100 rounded-lg text-gray-500 transition-colors"><ChevronRight className="w-5 h-5" /></button>
          </div>
          
          <div className="grid grid-cols-7 gap-y-4 gap-x-2 mb-2">
            {weekDays.map(d => (
              <div key={d} className="text-center text-xs font-bold text-gray-400 uppercase tracking-wider">{d}</div>
            ))}
            
            {blanks.map(b => (
              <div key={`blank-${b}`} className="h-10"></div>
            ))}
            
            {days.map(day => {
              const density = mockDensity(day);
              const isSelected = selectedDate === day;
              const isDisabled = density === 'disabled';
              
              return (
                <button
                  key={day}
                  disabled={isDisabled}
                  onClick={() => setSelectedDate(day)}
                  className="relative flex flex-col items-center justify-center h-12 w-full group"
                >
                  <div className={cn(
                    "w-9 h-9 flex items-center justify-center rounded-full text-sm transition-all",
                    isSelected ? "bg-[#0052CC] text-white font-bold shadow-md" : 
                    isDisabled ? "text-gray-300" : "text-gray-700 font-medium group-hover:bg-blue-50"
                  )}>
                    {day}
                  </div>
                  {/* Density Dot */}
                  <div className={cn(
                    "w-1.5 h-1.5 rounded-full mt-1 absolute bottom-0",
                    DENSITY_COLORS[density],
                    isSelected && density !== 'disabled' ? "opacity-100" : "opacity-80",
                    isDisabled && "hidden"
                  )} />
                </button>
              );
            })}
          </div>
        </div>

        {/* Legend */}
        <div className="flex items-center justify-between text-xs font-semibold text-gray-600 bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
          <div className="flex items-center gap-2">
            <span className="w-3 h-3 rounded-full bg-green-500 shadow-sm"></span>
            Available
          </div>
          <div className="flex items-center gap-2">
            <span className="w-3 h-3 rounded-full bg-yellow-400 shadow-sm"></span>
            Medium
          </div>
          <div className="flex items-center gap-2">
            <span className="w-3 h-3 rounded-full bg-red-500 shadow-sm"></span>
            Full
          </div>
        </div>
      </div>
      
      <div className="fixed bottom-0 left-0 right-0 max-w-md mx-auto p-4 bg-white border-t border-gray-100 z-20 flex gap-3 shadow-[0_-4px_20px_-10px_rgba(0,0,0,0.1)]">
        <button 
          onClick={() => navigate("/profile")}
          className="px-6 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-colors active:scale-[0.98]"
        >
          Back
        </button>
        <button 
          onClick={() => navigate("/booking/time")}
          disabled={!selectedDate || mockDensity(selectedDate) === 'high'}
          className="flex-1 bg-[#0052CC] hover:bg-blue-700 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed text-white font-bold py-4 rounded-xl transition-all shadow-sm active:scale-[0.98]"
        >
          Next Step
        </button>
      </div>
    </div>
  );
}
