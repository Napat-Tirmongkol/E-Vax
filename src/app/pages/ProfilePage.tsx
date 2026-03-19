import { useNavigate } from "react-router";

export function ProfilePage() {
  const navigate = useNavigate();

  return (
    <div className="p-5 flex flex-col h-full animate-in fade-in slide-in-from-right-4 duration-500">
      <div className="flex-1 space-y-6">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">Patient Profile</h2>
          <p className="text-sm text-gray-500 mt-1">Please enter your personal details below.</p>
        </div>

        <form className="space-y-5" onSubmit={(e) => { e.preventDefault(); navigate("/booking/date"); }}>
          <div className="space-y-1.5">
            <label className="text-sm font-semibold text-gray-700">Full Name</label>
            <input 
              type="text" 
              required
              placeholder="e.g. Somchai Jaidee"
              className="w-full p-4 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0052CC] focus:border-transparent outline-none transition-all placeholder:text-gray-400"
            />
          </div>
          
          <div className="space-y-1.5">
            <label className="text-sm font-semibold text-gray-700">ID / Passport Number</label>
            <input 
              type="text" 
              required
              placeholder="Enter 13-digit ID or Passport"
              className="w-full p-4 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0052CC] focus:border-transparent outline-none transition-all placeholder:text-gray-400"
            />
          </div>
          
          <div className="space-y-1.5">
            <label className="text-sm font-semibold text-gray-700">Phone Number</label>
            <input 
              type="tel" 
              required
              placeholder="08X-XXX-XXXX"
              className="w-full p-4 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0052CC] focus:border-transparent outline-none transition-all placeholder:text-gray-400"
            />
          </div>
        </form>
      </div>
      
      <div className="fixed bottom-0 left-0 right-0 max-w-md mx-auto p-4 bg-white border-t border-gray-100 z-20 flex gap-3 shadow-[0_-4px_20px_-10px_rgba(0,0,0,0.1)]">
        <button 
          onClick={() => navigate("/")}
          className="px-6 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-colors active:scale-[0.98]"
        >
          Back
        </button>
        <button 
          onClick={() => navigate("/booking/date")}
          className="flex-1 bg-[#0052CC] hover:bg-blue-700 text-white font-bold py-4 rounded-xl transition-all shadow-sm active:scale-[0.98]"
        >
          Continue
        </button>
      </div>
    </div>
  );
}
