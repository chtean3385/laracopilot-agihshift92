import { motion } from 'framer-motion';
import { useEffect, useState } from 'react';
import { MessageCircle, CheckCircle2 } from 'lucide-react';

export function Scene2() {
  const [phase, setPhase] = useState(0);

  useEffect(() => {
    const timers = [
      setTimeout(() => setPhase(1), 800),
      setTimeout(() => setPhase(2), 1600),
      setTimeout(() => setPhase(3), 3200),
    ];
    return () => timers.forEach(t => clearTimeout(t));
  }, []);

  return (
    <motion.div
      className="absolute inset-0 flex flex-col items-center justify-center p-8 bg-[#25D366]/10"
      initial={{ opacity: 0, x: 100 }}
      animate={{ opacity: 1, x: 0 }}
      exit={{ opacity: 0, x: -100, filter: 'blur(10px)' }}
      transition={{ type: 'spring', damping: 25, stiffness: 200 }}
    >
      <motion.div
        className="mb-8 p-6 bg-[#25D366] rounded-full shadow-[0_0_40px_rgba(37,211,102,0.4)]"
        initial={{ scale: 0 }}
        animate={{ scale: 1 }}
        transition={{ type: 'spring', delay: 0.3 }}
      >
        <MessageCircle className="w-12 h-12 text-white" />
      </motion.div>

      <div className="w-full max-w-[80vw] bg-[#1e293b] rounded-3xl p-6 shadow-2xl border border-white/10 relative overflow-hidden">
        <motion.div
          className="bg-[#25D366]/20 p-4 rounded-2xl rounded-tl-sm w-4/5 mb-4"
          initial={{ opacity: 0, scale: 0.8, x: -20 }}
          animate={{ opacity: 1, scale: 1, x: 0 }}
          transition={{ type: 'spring', delay: 0.6 }}
        >
          <p className="text-sm">Hi Alex, your booking at Dreams Hotel is confirmed!</p>
        </motion.div>
        
        <motion.div
          className="bg-[#25D366]/20 p-4 rounded-2xl rounded-tl-sm w-5/6"
          initial={{ opacity: 0, scale: 0.8, x: -20 }}
          animate={phase >= 2 ? { opacity: 1, scale: 1, x: 0 } : { opacity: 0, scale: 0.8, x: -20 }}
          transition={{ type: 'spring' }}
        >
          <p className="text-sm">Here is your digital check-in link and receipt. Have a great stay!</p>
        </motion.div>
      </div>

      <motion.div
        className="mt-12 text-center"
        initial={{ opacity: 0, y: 20 }}
        animate={phase >= 1 ? { opacity: 1, y: 0 } : { opacity: 0, y: 20 }}
        transition={{ delay: 0.8 }}
      >
        <h2 className="text-[7vw] font-bold text-white flex items-center justify-center gap-3">
          <CheckCircle2 className="w-8 h-8 text-[#25D366]" />
          Sent automatically
        </h2>
        <p className="text-[4vw] text-white/60 mt-2">Zero staff effort.</p>
      </motion.div>
    </motion.div>
  );
}
