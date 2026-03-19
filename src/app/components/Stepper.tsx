import { cn } from "../lib/utils";
import { Check } from "lucide-react";

export function Stepper({ currentStep }: { currentStep: number }) {
  const steps = [
    { num: 1, label: "Consent" },
    { num: 2, label: "Profile" },
    { num: 3, label: "Booking" },
    { num: 4, label: "Summary" }
  ];

  return (
    <div className="flex items-center justify-between w-full relative">
      <div className="absolute left-0 top-[35%] -translate-y-1/2 w-full h-0.5 bg-gray-200 -z-10" />
      <div 
        className="absolute left-0 top-[35%] -translate-y-1/2 h-0.5 bg-[#0052CC] -z-10 transition-all duration-300" 
        style={{ width: `${((currentStep - 1) / (steps.length - 1)) * 100}%` }}
      />
      {steps.map((step) => {
        const isCompleted = step.num < currentStep;
        const isActive = step.num === currentStep;
        
        return (
          <div key={step.num} className="flex flex-col items-center gap-2">
            <div className={cn(
              "w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold border-2 transition-colors",
              isCompleted ? "bg-[#0052CC] border-[#0052CC] text-white" : 
              isActive ? "bg-white border-[#0052CC] text-[#0052CC]" : 
              "bg-white border-gray-300 text-gray-400"
            )}>
              {isCompleted ? <Check className="w-4 h-4" /> : step.num}
            </div>
            <span className={cn(
              "text-[10px] font-medium uppercase tracking-wide",
              isActive || isCompleted ? "text-[#0052CC]" : "text-gray-400"
            )}>{step.label}</span>
          </div>
        );
      })}
    </div>
  );
}
