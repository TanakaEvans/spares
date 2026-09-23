export default function FormField({ label, htmlFor, error, required = false, help, children, className = '' }) {
    return (
        <div className={`space-y-1 ${className}`}>
            {label && (
                <label htmlFor={htmlFor} className="block text-sm font-medium text-slate-700">
                    {label}
                    {required && <span className="text-red-500 ml-0.5">*</span>}
                </label>
            )}
            {children}
            {help && !error && <p className="text-xs text-slate-400">{help}</p>}
            {error && <p className="text-xs text-red-600">{error}</p>}
        </div>
    );
}
