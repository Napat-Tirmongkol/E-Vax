import { useNavigate } from "react-router";
import { CheckCircle2, CalendarPlus, X, MapPin, Calendar, Clock, User, QrCode } from "lucide-react";

export function SummaryPage() {
  const navigate = useNavigate();

  return (
    <div className="p-5 flex flex-col h-full bg-[#f4f7fa] animate-in fade-in slide-in-from-bottom-8 duration-700">
      <div className="flex-1 flex flex-col items-center pb-24">
        {/* Success Icon & Header */}
        <div className="mt-6 mb-8 flex flex-col items-center text-center">
          <div className="relative mb-4">
            <div className="absolute inset-0 bg-green-200 rounded-full animate-ping opacity-20"></div>
            <div className="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center shadow-inner relative z-10">
              <CheckCircle2 className="w-14 h-14 text-green-500" />
            </div>
          </div>
          <h2 className="text-3xl font-extrabold text-gray-900 tracking-tight">Booking Confirmed!</h2>
          <p className="text-sm font-medium text-gray-500 mt-2">Please present your QR code on arrival</p>
        </div>

        {/* Summary Card (Ticket Style) */}
        <div className="w-full bg-white rounded-[24px] shadow-xl border border-gray-100 overflow-hidden relative">
          {/* Ticket styling cutouts */}
          <div className="absolute left-0 top-[55%] -mt-4 -ml-4 w-8 h-8 bg-[#f4f7fa] rounded-full border-r border-gray-100 shadow-inner" />
          <div className="absolute right-0 top-[55%] -mt-4 -mr-4 w-8 h-8 bg-[#f4f7fa] rounded-full border-l border-gray-100 shadow-inner" />
          <div className="absolute left-6 right-6 top-[55%] border-t-2 border-dashed border-gray-200" />
          
          <div className="p-7 pb-8">
            <h3 className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-6 text-center">Booking Details</h3>
            
            <div className="space-y-5">
              <div className="flex gap-4 items-start">
                <div className="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center shrink-0">
                  <User className="w-5 h-5 text-[#0052CC]" />
                </div>
                <div>
                  <p className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-0.5">Patient Name</p>
                  <p className="font-bold text-gray-900 text-lg">Somchai Jaidee</p>
                </div>
              </div>
              
              <div className="flex gap-4 items-start">
                <div className="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center shrink-0">
                  <Calendar className="w-5 h-5 text-[#0052CC]" />
                </div>
                <div>
                  <p className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-0.5">Date</p>
                  <p className="font-bold text-gray-900 text-lg">12 March 2026</p>
                </div>
              </div>
              
              <div className="flex gap-4 items-start">
                <div className="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center shrink-0">
                  <Clock className="w-5 h-5 text-[#0052CC]" />
                </div>
                <div>
                  <p className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-0.5">Time</p>
                  <p className="font-bold text-gray-900 text-lg">10:00 - 11:00 AM</p>
                </div>
              </div>
              
              <div className="flex gap-4 items-start">
                <div className="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center shrink-0">
                  <MapPin className="w-5 h-5 text-[#0052CC]" />
                </div>
                <div>
                  <p className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-0.5">Location</p>
                  <p className="font-bold text-gray-900 text-base leading-tight mt-1">Vax Center, Hospital 1<br/><span className="text-gray-500 font-medium text-sm">Building B, Floor 2</span></p>
                </div>
              </div>
            </div>
          </div>
          
          <div className="pt-8 pb-7 px-7 flex flex-col items-center justify-center bg-gray-50">
            <div className="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 mb-3 relative">
              <QrCode className="w-[140px] h-[140px] text-gray-900" />
            </div>
            <p className="text-sm font-bold font-mono tracking-widest text-gray-600 bg-gray-200 px-4 py-1.5 rounded-full">
              ID: EVAX-89321
            </p>
          </div>
        </div>
      </div>
      
      <div className="fixed bottom-0 left-0 right-0 max-w-md mx-auto p-4 bg-white border-t border-gray-100 z-20 flex flex-col gap-3 shadow-[0_-10px_30px_-15px_rgba(0,0,0,0.1)]">
        <button className="w-full flex items-center justify-center gap-2 border-2 border-[#0052CC] text-[#0052CC] font-bold py-4 rounded-xl hover:bg-blue-50 transition-colors active:scale-[0.98]">
          <CalendarPlus className="w-5 h-5" />
          Save to Calendar
        </button>
        <button 
          onClick={() => navigate("/")}
          className="w-full flex items-center justify-center gap-2 bg-[#0052CC] hover:bg-blue-700 text-white font-bold py-4 rounded-xl transition-colors shadow-sm active:scale-[0.98]"
        >
          <X className="w-5 h-5" />
          Close App
        </button>
      </div>
    </div>
  );
}
