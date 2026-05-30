import { motion } from 'framer-motion';
import { useEffect, useState } from 'react';
import { QrCode, Smartphone } from 'lucide-react';

export function Scene3() {
  const [phase, setPhase] = useState(0);

  useEffect(() => {
    const timers = [
      setTimeout(() => setPhase(1), 500),
      setTimeout(() => setPhase(2), 1500),
      setTimeout(() => setPhase(3), 3200),
    ];
    return () => timers.forEach(t => clearTimeout(t));
  }, []);

  return (
    <motion.div
      className="absolute inset-0 flex flex-col items-center justify-center p-8"
      initial={{ opacity: 0, scale: 0.8 }}
      animate={{ opacity: 1, scale: 1 }}
      exit={{ opacity: 0, y: -100 }}
      transition={{ duration: 0.6 }}
    >
      <div className="relative">
        <motion.div
          className="absolute inset-0 border-4 border-[#f59e0b] rounded-3xl"
          initial={{ opacity: 0, scale: 1.5 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 0.8, ease: 'easeOut' }}
        />
        
        <motion.div
          className="bg-white p-8 rounded-3xl"
          initial={{ opacity: 0, rotateY: 90 }}
          animate={{ opacity: 1, rotateY: 0 }}
          transition={{ duration: 0.8, delay: 0.2 }}
        >
          <QrCode className="w-32 h-32 text-[#0f172a]" />
        </motion.div>

        <motion.div
          className="absolute -right-8 -bottom-8 bg-[#1e293b] p-4 rounded-full border-2 border-[#f59e0b] shadow-xl"
          initial={{ opacity: 0, x: 50, y: 50 }}
          animate={phase >= 2 ? { opacity: 1, x: 0, y: 0 } : { opacity: 0, x: 50, y: 50 }}
          transition={{ type: 'spring', damping: 15 }}
        >
          <Smartphone className="w-12 h-12 text-white" />
        </motion.div>
      </div>

      <motion.div
        className="mt-16 text-center"
        initial={{ opacity: 0, y: 20 }}
        animate={phase >= 1 ? { opacity: 1, y: 0 } : { opacity: 0, y: 20 }}
      >
        <h2 className="text-[8vw] font-bold text-white">
          Self Check-In
        </h2>
        <motion.p 
          className="text-[4.5vw] text-[#f59e0b] mt-2"
          initial={{ opacity: 0 }}
          animate={phase >= 2 ? { opacity: 1 } : { opacity: 0 }}
        >
          Guests scan &amp; skip the desk
        </motion.p>
      </motion.div>
    </motion.div>
  );
}
