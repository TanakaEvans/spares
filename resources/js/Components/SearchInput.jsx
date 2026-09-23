import { useEffect, useRef, useState } from 'react';
import { Search, X } from 'lucide-react';

// Debounced search input. Scanner-friendly: Enter fires immediately (ui-rules §2).
export default function SearchInput({
    value: initialValue = '',
    onSearch,
    placeholder = 'Search…',
    delay = 300,
    autoFocus = false,
    className = '',
    id,
}) {
    const [value, setValue] = useState(initialValue);
    const timer = useRef(null);
    const inputRef = useRef(null);

    useEffect(() => setValue(initialValue), [initialValue]);

    function fire(v) {
        clearTimeout(timer.current);
        onSearch?.(v);
    }

    function handleChange(e) {
        const v = e.target.value;
        setValue(v);
        clearTimeout(timer.current);
        timer.current = setTimeout(() => onSearch?.(v), delay);
    }

    function handleKeyDown(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            fire(value);
        }
        if (e.key === 'Escape' && value) {
            setValue('');
            fire('');
        }
    }

    function clear() {
        setValue('');
        fire('');
        inputRef.current?.focus();
    }

    return (
        <div className={`relative ${className}`}>
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
            <input
                ref={inputRef}
                id={id}
                type="text"
                value={value}
                onChange={handleChange}
                onKeyDown={handleKeyDown}
                placeholder={placeholder}
                autoFocus={autoFocus}
                className="w-full rounded-lg border border-slate-300 pl-9 pr-8 py-2 text-sm focus:border-orange-500 focus:ring-orange-500"
            />
            {value && (
                <button
                    type="button"
                    onClick={clear}
                    aria-label="Clear search"
                    className="absolute right-2 top-1/2 -translate-y-1/2 p-0.5 rounded text-slate-400 hover:text-slate-600"
                >
                    <X className="w-4 h-4" />
                </button>
            )}
        </div>
    );
}
