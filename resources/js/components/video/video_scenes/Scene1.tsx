import { motion } from 'framer-motion';
import { useEffect, useState } from 'react';
import { Building2, Sparkles, Star } from 'lucide-react';

export function Scene1() {
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
      initial={{ opacity: 0, scale: 1.1 }}
      animate={{ opacity: 1, scale: 1 }}
      exit={{ opacity: 0, scale: 0.9, filter: 'blur(10px)' }}
      transition={{ duration: 0.8 }}
    >
      <motion.div
        className="absolute inset-0 border-[2px] border-[#f59e0b]/30 m-8 rounded-3xl"
        initial={{ pathLength: 0, opacity: 0 }}
        animate={{ pathLength: 1, opacity: 1 }}
        transition={{ duration: 1.5, ease: 'easeInOut' }}
      />
      
      <div className="relative z-10 flex flex-col items-center text-center">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
          className="mb-8"
        >
          <Building2 className="w-16 h-16 text-[#f59e0b]" />
        </motion.div>

        <h1 className="text-[12vw] font-bold leading-tight tracking-tighter">
          <motion.span
            className="block"
            initial={{ opacity: 0, y: 40 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ type: 'spring', damping: 20, delay: 0.5 }}
          >
            Your Hotel.
          </motion.span>
          <motion.span
            className="block text-[#f59e0b]"
            initial={{ opacity: 0, y: 40 }}
            animate={phase >= 2 ? { opacity: 1, y: 0 } : { opacity: 0, y: 40 }}
            transition={{ type: 'spring', damping: 20 }}
          >
            Automated.
          </motion.span>
        </h1>
      </div>

      <motion.div
        className="absolute top-1/4 right-1/4"
        animate={{ y: [0, -20, 0], rotate: [0, 10, -10, 0] }}
        transition={{ duration: 4, repeat: Infinity }}
      >
        <Sparkles className="w-8 h-8 text-white/50" />
      </motion.div>
      <motion.div
        className="absolute bottom-1/4 left-1/4"
        animate={{ y: [0, 20, 0], rotate: [0, -10, 10, 0] }}
        transition={{ duration: 5, repeat: Infinity }}
      >
        <Star className="w-6 h-6 text-white/40" />
      </motion.div>
    </motion.div>
  );
}
