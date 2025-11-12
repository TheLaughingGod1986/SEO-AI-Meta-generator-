import * as React from 'react';

export interface SpinnerProps {
	size?: number;
	className?: string;
}

const Spinner: React.FC<SpinnerProps> = ({ size = 18, className = '' }) => {
	const strokeWidth = 4;
	return (
		<svg
			className={`animate-spin text-white ${className}`}
			width={size}
			height={size}
			viewBox="0 0 24 24"
			role="status"
			aria-live="polite"
		>
			<title>Loading</title>
			<circle
				className="opacity-25"
				cx="12"
				cy="12"
				r="10"
				stroke="currentColor"
				strokeWidth={strokeWidth}
				fill="none"
			/>
			<path
				className="opacity-75"
				fill="currentColor"
				d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
			/>
		</svg>
	);
};

export default Spinner;

