type FocusTrapCleanup = () => void;

const FOCUSABLE_SELECTORS = [
	'a[href]',
	'area[href]',
	'input:not([disabled]):not([type="hidden"])',
	'select:not([disabled])',
	'textarea:not([disabled])',
	'button:not([disabled])',
	'iframe',
	'object',
	'embed',
	'[contenteditable]',
	'[tabindex]:not([tabindex="-1"])',
].join(',');

export const createFocusTrap = (
	container: HTMLElement,
	initialFocus?: HTMLElement | null
): FocusTrapCleanup => {
	const focusableEls = Array.from(container.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTORS));
	const firstFocusable = focusableEls[0] ?? container;
	const lastFocusable = focusableEls[focusableEls.length - 1] ?? container;

	const previouslyFocusedElement = document.activeElement as HTMLElement | null;

	const handleKeyDown = (event: KeyboardEvent) => {
		if (event.key !== 'Tab') {
			return;
		}

		if (focusableEls.length === 0) {
			event.preventDefault();
			container.focus();
			return;
		}

		if (event.shiftKey) {
			if (document.activeElement === firstFocusable) {
				event.preventDefault();
				lastFocusable.focus();
			}
		} else {
			if (document.activeElement === lastFocusable) {
				event.preventDefault();
				firstFocusable.focus();
			}
		}
	};

	document.addEventListener('keydown', handleKeyDown);

	const focusTarget = initialFocus ?? firstFocusable;
	window.requestAnimationFrame(() => {
		focusTarget.focus();
	});

	return () => {
		document.removeEventListener('keydown', handleKeyDown);
		if (previouslyFocusedElement) {
			previouslyFocusedElement.focus();
		}
	};
};

