import { motion } from 'framer-motion';
import { useEffect, useState } from 'react';
import { Building2 } from 'lucide-react';

export function Scene5() {
  const [phase, setPhase] = useState(0);

  useEffect(() => {
    const timers = [
      setTimeout(() => setPhase(1), 500),
      setTimeout(() => setPhase(2), 1500),
    ];
    return () => timers.forEach(t => clearTimeout(t));
  }, []);

  return (
    <motion.div
      className="absolute inset-0 flex flex-col items-center justify-center p-8 bg-[#0f172a]"
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      transition={{ duration: 1 }}
    >
      <motion.div
        className="absolute inset-0"
        initial={{ opacity: 0 }}
        animate={{ opacity: 0.5 }}
        transition={{ duration: 2 }}
        style={{
          background: 'radial-gradient(circle at center, #1e3a8a 0%, #0f172a 70%)',
        }}
      />

      <div className="relative z-10 flex flex-col items-center text-center">
        <motion.div
          initial={{ scale: 0, rotate: -180 }}
          animate={{ scale: 1, rotate: 0 }}
          transition={{ type: 'spring', damping: 20, stiffness: 100 }}
          className="mb-8"
        >
          <div className="bg-[#f59e0b] p-6 rounded-2xl shadow-[0_0_40px_rgba(245,158,11,0.4)]">
            <Building2 className="w-16 h-16 text-[#0f172a]" />
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={phase >= 1 ? { opacity: 1, y: 0 } : { opacity: 0, y: 20 }}
          transition={{ duration: 0.6 }}
        >
          <h1 className="text-[9vw] font-bold text-white tracking-tight">
            Dreams Technology
          </h1>
          <h2 className="text-[5vw] text-[#f59e0b] font-medium tracking-wide">
            Hotel CRM
          </h2>
        </motion.div>

        <motion.div
          className="mt-12 border-t border-white/20 pt-8 w-full max-w-[80%]"
          initial={{ opacity: 0, scale: 0.9 }}
          animate={phase >= 2 ? { opacity: 1, scale: 1 } : { opacity: 0, scale: 0.9 }}
          transition={{ duration: 0.8 }}
        >
          <p className="text-[5vw] italic text-white/80 font-serif">
            "Smart Hotels Run on Dreams"
          </p>
        </motion.div>
      </div>
    </motion.div>
  );
}
