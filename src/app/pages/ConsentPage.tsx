import { useNavigate } from "react-router";
import { useState } from "react";
import { ShieldAlert } from "lucide-react";

export function ConsentPage() {
  const navigate = useNavigate();
  const [agreed, setAgreed] = useState(false);

  return (
    <div className="p-5 flex flex-col h-full animate-in fade-in slide-in-from-bottom-4 duration-500">
      <div className="flex-1">
        <div className="flex items-center gap-2 mb-4 text-[#0052CC]">
          <ShieldAlert className="w-6 h-6" />
          <h2 className="text-xl font-bold text-gray-800">Terms & Conditions</h2>
        </div>
        
        <div className="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 text-sm text-gray-600 space-y-4 mb-6 leading-relaxed">
          <p className="font-medium text-gray-800">Welcome to E-Vax Vaccination Booking.</p>
          <p>By proceeding with this booking, you consent to the collection and processing of your personal and medical data for the purpose of vaccination scheduling and administration.</p>
          <p>Your information will be kept strictly confidential and used securely in accordance with standard medical privacy policies and national healthcare regulations.</p>
        </div>
        
        <label className="flex items-start gap-3 p-4 bg-white rounded-2xl border border-gray-100 shadow-sm cursor-pointer hover:bg-gray-50 transition-colors">
          <input 
            type="checkbox" 
            className="mt-1 w-5 h-5 rounded border-gray-300 text-[#0052CC] focus:ring-[#0052CC] cursor-pointer"
            checked={agreed}
            onChange={(e) => setAgreed(e.target.checked)}
          />
          <span className="text-sm text-gray-700 font-medium leading-tight">I have read, understood, and agree to the terms and conditions and privacy policy.</span>
        </label>
      </div>
      
      <div className="fixed bottom-0 left-0 right-0 max-w-md mx-auto p-4 bg-white border-t border-gray-100 z-20 shadow-[0_-4px_20px_-10px_rgba(0,0,0,0.1)]">
        <button 
          onClick={() => navigate("/profile")}
          disabled={!agreed}
          className="w-full bg-[#0052CC] hover:bg-blue-700 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed text-white font-bold py-4 rounded-xl transition-all shadow-sm active:scale-[0.98]"
        >
          I Agree, Continue
        </button>
      </div>
    </div>
  );
}
