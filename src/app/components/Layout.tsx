import { Outlet, useLocation } from "react-router";
import { Stepper } from "./Stepper";

export function Layout() {
  const location = useLocation();
  const path = location.pathname;
  let currentStep = 1;
  
  if (path.includes("/profile")) currentStep = 2;
  else if (path.includes("/booking")) currentStep = 3;
  else if (path.includes("/summary")) currentStep = 4;

  return (
    <div className="min-h-screen bg-gray-900 flex items-start justify-center font-sans">
      <div className="w-full max-w-md bg-gray-50 min-h-screen shadow-2xl flex flex-col relative overflow-hidden">
        {/* Header */}
        <header className="bg-[#0052CC] text-white p-4 sticky top-0 z-10 flex items-center justify-center">
          <h1 className="text-xl font-bold tracking-wide">E-Vax</h1>
        </header>
        
        {/* Stepper Container */}
        <div className="px-6 py-5 bg-white border-b border-gray-100 z-10 shadow-sm">
          <Stepper currentStep={currentStep} />
        </div>
        
        {/* Main Content Area */}
        <main className="flex-1 overflow-y-auto bg-gray-50 flex flex-col pb-[100px]">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
