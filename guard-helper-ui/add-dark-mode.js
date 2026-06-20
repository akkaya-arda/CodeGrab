const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'src/app/pages/public-grab-code/public-grab-code.html');
let content = fs.readFileSync(filePath, 'utf8');

// Replace root div conditionally adding dark mode
content = content.replace(
  '<div\n    class="min-h-screen w-full bg-slate-100',
  '<div\n    [class.dark]="isDarkMode()"\n    class="min-h-screen w-full bg-slate-100 dark:bg-slate-900'
);

// Define dark mode mappings
const replacements = {
  'bg-slate-100': 'bg-slate-100 dark:bg-slate-900',
  'text-slate-850': 'text-slate-850 dark:text-slate-100',
  'text-slate-800': 'text-slate-800 dark:text-slate-200',
  'text-slate-700': 'text-slate-700 dark:text-slate-300',
  'text-slate-600': 'text-slate-600 dark:text-slate-400',
  'text-slate-500': 'text-slate-500 dark:text-slate-400',
  'text-slate-400': 'text-slate-400 dark:text-slate-500',
  'bg-white': 'bg-white dark:bg-slate-800',
  'bg-slate-50': 'bg-slate-50 dark:bg-slate-800/50',
  'border-slate-200': 'border-slate-200 dark:border-slate-700',
  'border-slate-300': 'border-slate-300 dark:border-slate-600',
  'border-slate-150': 'border-slate-150 dark:border-slate-700',
  'border-slate-100': 'border-slate-100 dark:border-slate-700/50',
  'hover:bg-slate-50': 'hover:bg-slate-50 dark:hover:bg-slate-700',
  'hover:bg-slate-100': 'hover:bg-slate-100 dark:hover:bg-slate-700',
  'bg-slate-900/30': 'bg-slate-900/30 dark:bg-black/60',
  'bg-white/90': 'bg-white/90 dark:bg-slate-900/90',
  'bg-white/50': 'bg-white/50 dark:bg-slate-900/50',
  'bg-slate-50/80': 'bg-slate-50/80 dark:bg-slate-800/80',
  'border-white/20': 'border-white/20 dark:border-slate-700',
  'shadow-md': 'shadow-md dark:shadow-none',
  'shadow-lg': 'shadow-lg dark:shadow-none',
  'shadow-xl': 'shadow-xl dark:shadow-none',
  'shadow-2xl': 'shadow-2xl dark:shadow-none',
  'bg-amber-50': 'bg-amber-50 dark:bg-amber-900/20',
  'border-amber-200': 'border-amber-200 dark:border-amber-700/50',
  'text-amber-600': 'text-amber-600 dark:text-amber-400',
  'bg-red-50': 'bg-red-50 dark:bg-red-900/20',
  'border-red-200': 'border-red-200 dark:border-red-700/50',
  'text-red-600': 'text-red-600 dark:text-red-400',
  'bg-emerald-50': 'bg-emerald-50 dark:bg-emerald-900/20',
  'border-emerald-200': 'border-emerald-200 dark:border-emerald-700/50',
  'text-emerald-700': 'text-emerald-700 dark:text-emerald-400',
  'text-emerald-600': 'text-emerald-600 dark:text-emerald-400',
  'border-indigo-100/80': 'border-indigo-100/80 dark:border-indigo-500/30',
  'bg-indigo-50': 'bg-indigo-50 dark:bg-indigo-900/20',
  'border-indigo-100': 'border-indigo-100 dark:border-indigo-800/50',
  'text-indigo-700': 'text-indigo-700 dark:text-indigo-400',
  'text-indigo-600': 'text-indigo-600 dark:text-indigo-400',
};

// Also inject the dark mode toggle switch at the top right
const headerDivRegex = /(<div class="text-center mb-8 flex flex-col items-center">)/;
const switchHtml = `
        <!-- Dark Mode Toggle -->
        <div class="absolute top-4 right-4 sm:top-6 sm:right-6 z-50">
            <button (click)="toggleDarkMode()" class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 shadow-sm flex items-center justify-center transition-colors cursor-pointer">
                @if (isDarkMode()) {
                    <i class="fa-solid fa-moon"></i>
                } @else {
                    <i class="fa-solid fa-sun"></i>
                }
            </button>
        </div>

        $1`;

content = content.replace(headerDivRegex, switchHtml);

// Apply mappings. Note: we need to replace carefully so we don't replace inside already replaced strings.
// A safe way is to split by spaces/quotes and replace only exact matches, but regex with word boundaries works for classes.
Object.keys(replacements).forEach(key => {
  // Use regex to replace full class names only (e.g. bg-white, not bg-white/90 unless intended)
  const escapedKey = key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const regex = new RegExp(`(?<=[ "'\\\`])(${escapedKey})(?=[ "'\\\`])`, 'g');
  
  // Wait, if we replace bg-white to bg-white dark:bg-slate-800, doing it globally might replace it twice if we run script multiple times.
  // The user environment is fresh so it's fine.
  content = content.replace(regex, replacements[key]);
});

fs.writeFileSync(filePath, content, 'utf8');
console.log('Updated public-grab-code.html with dark mode classes');
