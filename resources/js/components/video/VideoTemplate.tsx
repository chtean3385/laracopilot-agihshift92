import { motion, AnimatePresence } from 'framer-motion';
import { useVideoPlayer } from '@/lib/video';
import { Scene1 } from './video_scenes/Scene1';
import { Scene2 } from './video_scenes/Scene2';
import { Scene3 } from './video_scenes/Scene3';
import { Scene4 } from './video_scenes/Scene4';
import { Scene5 } from './video_scenes/Scene5';

const SCENE_DURATIONS = {
  hook: 4000,
  whatsapp: 4000,
  qr: 4000,
  ota: 4000,
  close: 4000
};

export default function VideoTemplate() {
  const { currentScene } = useVideoPlayer({ durations: SCENE_DURATIONS });

  return (
    <div className="relative w-full h-screen overflow-hidden bg-[#0f172a] text-white font-sans flex items-center justify-center">
      {/* Background Layer */}
      <div className="absolute inset-0">
        <img
          src={`${import.meta.env.BASE_URL}images/bg-navy-gold.png`}
          className="absolute inset-0 w-full h-full object-cover opacity-30"
          alt=""
        />
        {/* Animated Gradient Orbs */}
        <motion.div
          className="absolute w-[80vw] h-[80vw] rounded-full blur-[100px] opacity-20"
          style={{ background: 'radial-gradient(circle, #f59e0b, transparent)' }}
          animate={{ x: ['-20%', '20%', '-10%'], y: ['-10%', '30%', '10%'] }}
          transition={{ duration: 10, repeat: Infinity, ease: 'easeInOut' }}
        />
        <motion.div
          className="absolute w-[90vw] h-[90vw] rounded-full blur-[120px] opacity-30 right-0 bottom-0"
          style={{ background: 'radial-gradient(circle, #1e3a8a, transparent)' }}
          animate={{ x: ['10%', '-20%', '0%'], y: ['20%', '-10%', '20%'] }}
          transition={{ duration: 15, repeat: Infinity, ease: 'easeInOut' }}
        />
      </div>

      <AnimatePresence mode="popLayout">
        {currentScene === 0 && <Scene1 key="hook" />}
        {currentScene === 1 && <Scene2 key="whatsapp" />}
        {currentScene === 2 && <Scene3 key="qr" />}
        {currentScene === 3 && <Scene4 key="ota" />}
        {currentScene === 4 && <Scene5 key="close" />}
      </AnimatePresence>
    </div>
  );
}
