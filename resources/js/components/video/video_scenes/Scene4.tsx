import { motion } from 'framer-motion';
import { useEffect, useState } from 'react';
import { Globe, ArrowDownToLine, Database } from 'lucide-react';

export function Scene4() {
  const [phase, setPhase] = useState(0);

  useEffect(() => {
    const timers = [
      setTimeout(() => setPhase(1), 600),
      setTimeout(() => setPhase(2), 1400),
      setTimeout(() => setPhase(3), 3200),
    ];
    return () => timers.forEach(t => clearTimeout(t));
  }, []);

  return (
    <motion.div
      className="absolute inset-0 flex flex-col items-center justify-center p-8"
      initial={{ opacity: 0, rotateX: 90 }}
      animate={{ opacity: 1, rotateX: 0 }}
      exit={{ opacity: 0, scale: 1.2, filter: 'blur(10px)' }}
      transition={{ duration: 0.8 }}
      style={{ perspective: '1000px' }}
    >
      <div className="flex w-full justify-between items-center mb-12 px-4">
        <motion.div
          className="bg-white/10 p-4 rounded-xl border border-white/20"
          initial={{ opacity: 0, x: -50 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ type: 'spring', delay: 0.2 }}
        >
          <p className="font-bold text-white text-lg">Booking.com</p>
        </motion.div>
        
        <motion.div
          className="bg-white/10 p-4 rounded-xl border border-white/20"
          initial={{ opacity: 0, x: 50 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ type: 'spring', delay: 0.4 }}
        >
          <p className="font-bold text-white text-lg">Airbnb</p>
        </motion.div>
      </div>

      <motion.div
        initial={{ opacity: 0, height: 0 }}
        animate={phase >= 1 ? { opacity: 1, height: 60 } : { opacity: 0, height: 0 }}
        className="mb-8"
      >
        <ArrowDownToLine className="w-12 h-12 text-[#f59e0b] animate-bounce" />
      </motion.div>

      <motion.div
        className="bg-[#1e293b] w-full p-8 rounded-3xl border border-[#f59e0b] shadow-[0_0_50px_rgba(245,158,11,0.2)] flex flex-col items-center"
        initial={{ opacity: 0, y: 50 }}
        animate={phase >= 2 ? { opacity: 1, y: 0 } : { opacity: 0, y: 50 }}
        transition={{ type: 'spring', damping: 20 }}
      >
        <Database className="w-16 h-16 text-[#f59e0b] mb-4" />
        <h3 className="text-2xl font-bold text-white">Central CRM</h3>
        <p className="text-white/60">Live sync instantly</p>
      </motion.div>

      <motion.div
        className="mt-12 text-center"
        initial={{ opacity: 0 }}
        animate={phase >= 2 ? { opacity: 1 } : { opacity: 0 }}
        transition={{ delay: 0.5 }}
      >
        <h2 className="text-[7vw] font-bold text-white">
          All Bookings.
        </h2>
        <h2 className="text-[7vw] font-bold text-[#f59e0b]">
          One Place.
        </h2>
      </motion.div>
    </motion.div>
  );
}
