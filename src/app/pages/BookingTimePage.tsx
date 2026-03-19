import { useNavigate } from "react-router";
import { useState } from "react";
import { Clock, Users, ArrowRight } from "lucide-react";
import { cn } from "../lib/utils";

type TimeSlot = { id: string, time: string, capacity: number, max: number };
const SLOTS: TimeSlot[] = [
  { id: '1', time: '08:00 - 09:00', capacity: 15, max: 20 },
  { id: '2', time: '09:00 - 10:00', capacity: 0, max: 20 }, // Full
  { id: '3', time: '10:00 - 11:00', capacity: 5, max: 20 },
  { id: '4', time: '11:00 - 12:00', capacity: 12, max: 20 },
  { id: '5', time: '13:00 - 14:00', capacity: 2, max: 20 },
  { id: '6', time: '14:00 - 15:00', capacity: 18, max: 20 },
];

export function BookingTimePage() {
  const navigate = useNavigate();
  const [selectedSlot, setSelectedSlot] = useState<string | null>(null);

  return (
    <div className="p-5 flex flex-col h-full animate-in fade-in slide-in-from-right-4 duration-500">
      <div className="flex-1">
        {/* Date Header */}
        <div className="text-center mb-6 bg-white p-4 rounded-2xl border border-blue-100 shadow-sm relative overflow-hidden">
          <div className="absolute top-0 left-0 w-1.5 h-full bg-[#0052CC]" />
          <p className="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Selected Date</p>
          <h2 className="text-xl font-bold text-[#0052CC]">12 March 2026</h2>
        </div>

        <div className="flex items-center gap-2 mb-5">
          <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
            <Clock className="w-5 h-5 text-[#0052CC]" />
          </div>
          <div>
            <h2 className="text-xl font-bold text-gray-900">Select Time</h2>
            <p className="text-xs text-gray-500">Choose an available slot</p>
          </div>
        </div>

        <div className="space-y-3.5">
          {SLOTS.map(slot => {
            const isFull = slot.capacity === 0;
            const isSelected = selectedSlot === slot.id;
            
            return (
              <button
                key={slot.id}
                disabled={isFull}
                onClick={() => setSelectedSlot(slot.id)}
                className={cn(
                  "w-full text-left p-5 rounded-2xl border-2 transition-all flex items-center justify-between group",
                  isFull ? "bg-gray-50 border-gray-100 opacity-60 cursor-not-allowed" : 
                  isSelected ? "bg-blue-50/50 border-[#0052CC] shadow-md" : 
                  "bg-white border-transparent shadow-sm hover:border-blue-200 hover:shadow-md"
                )}
              >
                <div>
                  <h3 className={cn(
                    "font-bold text-lg mb-0.5", 
                    isFull ? "text-gray-400" : isSelected ? "text-[#0052CC]" : "text-gray-800"
                  )}>
                    {slot.time}
                  </h3>
                  {!isFull && (
                    <p className="text-xs text-gray-500 flex items-center gap-1">
                      <Clock className="w-3 h-3" /> Duration: 1 Hour
                    </p>
                  )}
                </div>
                
                <div className={cn(
                  "flex items-center gap-1.5 text-xs font-bold px-3.5 py-1.5 rounded-full border",
                  isFull ? "bg-gray-100 text-gray-500 border-gray-200" : 
                  isSelected ? "bg-[#0052CC] text-white border-[#0052CC]" : "bg-green-50 text-green-700 border-green-200"
                )}>
                  {isFull ? (
                    "Fully Booked"
                  ) : (
                    <>
                      <Users className="w-3.5 h-3.5" />
                      {slot.capacity} left
                    </>
                  )}
                </div>
              </button>
            );
          })}
        </div>
      </div>
      
      <div className="fixed bottom-0 left-0 right-0 max-w-md mx-auto p-4 bg-white border-t border-gray-100 z-20 flex gap-3 shadow-[0_-4px_20px_-10px_rgba(0,0,0,0.1)]">
        <button 
          onClick={() => navigate("/booking/date")}
          className="px-6 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-colors active:scale-[0.98]"
        >
          Back
        </button>
        <button 
          onClick={() => navigate("/summary")}
          disabled={!selectedSlot}
          className="flex-1 flex items-center justify-center gap-2 bg-[#0052CC] hover:bg-blue-700 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed text-white font-bold py-4 rounded-xl transition-all shadow-sm active:scale-[0.98]"
        >
          Confirm Time <ArrowRight className="w-4 h-4" />
        </button>
      </div>
    </div>
  );
}
