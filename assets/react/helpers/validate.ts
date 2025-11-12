export const isValidEmail = (value: string): boolean => {
	return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim());
};

export const validatePassword = (value: string): string | null => {
	if (!value) {
		return 'Password is required.';
	}
	if (value.length < 8) {
		return 'Password must be at least 8 characters.';
	}
	return null;
};

export const validateRequired = (value: string, fieldName: string): string | null => {
	if (!value.trim()) {
		return `${fieldName} is required.`;
	}
	return null;
};

