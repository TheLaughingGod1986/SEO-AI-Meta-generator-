import * as React from 'react';
import Spinner from './Spinner';
import { isValidEmail, validatePassword, validateRequired } from '../helpers/validate';
import { createFocusTrap } from '../utils/focusTrap';

type AuthMode = 'login' | 'register';

interface AuthModalProps {
	isOpen: boolean;
	onClose: () => void;
	onSuccess: () => void;
}

interface FormErrors {
	email?: string;
	password?: string;
	form?: string;
}

const SEGMENTED_TABS: { label: string; value: AuthMode }[] = [
	{ label: 'Log in', value: 'login' },
	{ label: 'Create account', value: 'register' },
];

const AuthModal: React.FC<AuthModalProps> = ({ isOpen, onClose, onSuccess }) => {
	const [mode, setMode] = React.useState<AuthMode>('login');
	const [email, setEmail] = React.useState('');
	const [password, setPassword] = React.useState('');
	const [marketingOptIn, setMarketingOptIn] = React.useState(true);
	const [errors, setErrors] = React.useState<FormErrors>({});
	const [loading, setLoading] = React.useState(false);
	const [formError, setFormError] = React.useState<string | null>(null);
	const [toastMessage, setToastMessage] = React.useState<string | null>(null);
	const [showPassword, setShowPassword] = React.useState(false);

	const containerRef = React.useRef<HTMLDivElement>(null);
	const emailInputRef = React.useRef<HTMLInputElement>(null);
	const focusTrapCleanup = React.useRef<(() => void) | null>(null);
	const previouslyFocusedElement = React.useRef<HTMLElement | null>(null);

	const resetForm = React.useCallback(
		(nextMode: AuthMode) => {
			setMode(nextMode);
			setErrors({});
			setFormError(null);
			setPassword('');
			if (nextMode === 'login') {
				setMarketingOptIn(true);
			}
		},
		[setMode]
	);

	const closeModal = React.useCallback(() => {
		if (focusTrapCleanup.current) {
			focusTrapCleanup.current();
			focusTrapCleanup.current = null;
		}
		setLoading(false);
		setFormError(null);
		setErrors({});
		onClose();
	}, [onClose]);

	React.useEffect(() => {
		if (!isOpen) {
			return;
		}

		previouslyFocusedElement.current = document.activeElement as HTMLElement | null;

		const container = containerRef.current;
		if (container) {
			focusTrapCleanup.current = createFocusTrap(container, emailInputRef.current);
		}

		const handleKeyDown = (event: KeyboardEvent) => {
			if (event.key === 'Escape') {
				event.preventDefault();
				closeModal();
			}
		};

		document.addEventListener('keydown', handleKeyDown);

		return () => {
			document.removeEventListener('keydown', handleKeyDown);
			if (focusTrapCleanup.current) {
				focusTrapCleanup.current();
				focusTrapCleanup.current = null;
			}
		};
	}, [isOpen, closeModal]);

	React.useEffect(() => {
		if (!isOpen) {
			return;
		}
		setTimeout(() => {
			emailInputRef.current?.focus();
		}, 10);
	}, [isOpen, mode]);

	React.useEffect(() => {
		if (!toastMessage) {
			return;
		}
		const timer = window.setTimeout(() => {
			setToastMessage(null);
		}, 2400);
		return () => {
			window.clearTimeout(timer);
		};
	}, [toastMessage]);

	const handleBackdropClick = (event: React.MouseEvent<HTMLDivElement>) => {
		if (event.target === event.currentTarget) {
			closeModal();
		}
	};

	const validateForm = (): boolean => {
		const nextErrors: FormErrors = {};

		if (!isValidEmail(email)) {
			nextErrors.email = 'Please enter a valid email address.';
		}

		const passwordError = validatePassword(password);
		if (passwordError) {
			nextErrors.password = passwordError;
		}

		if (mode === 'register') {
			const emailRequired = validateRequired(email, 'Email');
			if (emailRequired) {
				nextErrors.email = emailRequired;
			}
		}

		setErrors(nextErrors);
		return Object.keys(nextErrors).length === 0;
	};

	const simulateRequest = async (endpoint: string, payload: Record<string, unknown>) => {
		// Mock API handler for now – replace with real fetch when backend is ready.
		await new Promise((resolve) => setTimeout(resolve, 900));

		const shouldFail = typeof payload.email === 'string' && payload.email.includes('fail');
		if (shouldFail) {
			const error = new Error('Invalid credentials. Try again or reset your password.');
			throw error;
		}

		return { endpoint };
	};

	const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
		event.preventDefault();
		setFormError(null);

		if (!validateForm()) {
			return;
		}

		setLoading(true);

		const payload =
			mode === 'login'
				? { email, password }
				: { email, password, marketingOptIn };

		const endpoint =
			mode === 'login'
				? '/api/auth/login'
				: '/api/auth/register';

		try {
			await simulateRequest(endpoint, payload);
			setToastMessage("You're in. Redirecting…");
			setTimeout(() => {
				onSuccess();
				closeModal();
			}, 900);
		} catch (error) {
			const message =
				error instanceof Error
					? error.message || 'Invalid credentials. Try again or reset your password.'
					: 'Invalid credentials. Try again or reset your password.';
			setFormError(message);
			setToastMessage(null);
		} finally {
			setLoading(false);
		}
	};

	const handleForgotPassword = async (event: React.MouseEvent<HTMLButtonElement>) => {
		event.preventDefault();
		setFormError(null);

		if (!isValidEmail(email)) {
			setErrors((prev) => ({
				...prev,
				email: 'Please enter your account email before resetting your password.',
			}));
			return;
		}

		setLoading(true);
		try {
			await simulateRequest('/api/auth/forgot', { email });
			setToastMessage('Password reset link sent! Check your inbox.');
		} catch (error) {
			const message =
				error instanceof Error ? error.message : 'Unable to send reset link. Please try again.';
			setFormError(message);
		} finally {
			setLoading(false);
		}
	};

	const renderFormDescription = () => {
		if (mode === 'register') {
			return (
				<p className="mt-3 text-sm font-medium text-emerald-600">
					No card required — includes 10 free AI generations/month.
				</p>
			);
		}
		return null;
	};

	if (!isOpen) {
		return null;
	}

	return (
		<div
			className="fixed inset-0 z-[1200] flex items-center justify-center bg-slate-900/40 px-4 py-6"
			onMouseDown={handleBackdropClick}
		>
			<div
				ref={containerRef}
				className="mx-auto w-[92vw] max-w-[420px] rounded-xl border border-slate-200 bg-white p-6 shadow-md focus:outline-none"
				role="dialog"
				aria-modal="true"
				aria-labelledby="seo-ai-meta-auth-modal-title"
				aria-busy={loading ? 'true' : 'false'}
				tabIndex={-1}
				onMouseDown={(event) => event.stopPropagation()}
			>
				{/* Header */}
				<div className="flex items-start justify-between">
					<div>
						<div className="flex items-center gap-3">
							<div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-white">
								<span className="text-sm font-bold tracking-wide">SO</span>
							</div>
							<div>
								<h2
									id="seo-ai-meta-auth-modal-title"
									className="text-xl font-semibold text-slate-900"
								>
									SEO AI Meta
								</h2>
								<p className="text-sm text-slate-500">
									Smart SEO titles &amp; meta descriptions powered by AI.
								</p>
							</div>
						</div>
					</div>
					<button
						type="button"
						onClick={closeModal}
						className="rounded-full p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
						aria-label="Close authentication modal"
					>
						<svg
							xmlns="http://www.w3.org/2000/svg"
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth={2}
							className="h-5 w-5"
						>
							<path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
						</svg>
					</button>
				</div>

				{/* Segmented control */}
				<div className="mt-6">
					<div className="grid grid-cols-2 rounded-lg bg-slate-100 p-1">
						{SEGMENTED_TABS.map((tab) => {
							const isActive = mode === tab.value;
							return (
								<button
									key={tab.value}
									type="button"
									onClick={() => resetForm(tab.value)}
									className={`rounded-md px-3 py-2 text-sm font-medium transition ${
										isActive
											? 'bg-white shadow-sm ring-1 ring-slate-200 text-slate-900'
											: 'text-slate-500 hover:text-slate-700'
									}`}
									aria-pressed={isActive}
								>
									{tab.label}
								</button>
							);
						})}
					</div>
				</div>

				<form className="mt-6 space-y-5" onSubmit={handleSubmit}>
					{formError && (
						<div
							className="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-600"
							role="alert"
						>
							{formError}
						</div>
					)}

					<div>
						<label htmlFor="seo-ai-meta-auth-email" className="block text-sm font-medium text-slate-700">
							Email
						</label>
						<input
							id="seo-ai-meta-auth-email"
							ref={emailInputRef}
							type="email"
							autoComplete="email"
							className={`mt-1 w-full rounded-lg border px-3 py-2 text-[15px] outline-none transition ${
								errors.email
									? 'border-rose-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-300'
									: 'border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500'
							}`}
							value={email}
							onChange={(event) => setEmail(event.target.value)}
							aria-invalid={Boolean(errors.email)}
							aria-describedby={errors.email ? 'seo-ai-meta-auth-email-error' : undefined}
							disabled={loading}
						/>
						{errors.email && (
							<p id="seo-ai-meta-auth-email-error" className="mt-1 text-sm text-rose-600">
								{errors.email}
							</p>
						)}
					</div>

					<div>
						<div className="flex items-center justify-between">
							<label htmlFor="seo-ai-meta-auth-password" className="block text-sm font-medium text-slate-700">
								Password
							</label>
							{mode === 'login' && (
								<button
									type="button"
									onClick={handleForgotPassword}
									className="text-sm font-medium text-blue-600 transition hover:text-blue-500"
									disabled={loading}
								>
									Forgot password?
								</button>
							)}
						</div>
						<div className="relative mt-1">
							<input
								id="seo-ai-meta-auth-password"
								type={showPassword ? 'text' : 'password'}
								autoComplete={mode === 'login' ? 'current-password' : 'new-password'}
								className={`w-full rounded-lg border px-3 py-2 text-[15px] outline-none transition ${
									errors.password
										? 'border-rose-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-300'
										: 'border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500'
								}`}
								value={password}
								onChange={(event) => setPassword(event.target.value)}
								aria-invalid={Boolean(errors.password)}
								aria-describedby={errors.password ? 'seo-ai-meta-auth-password-error' : undefined}
								disabled={loading}
							/>
							<button
								type="button"
								onClick={() => setShowPassword((prev) => !prev)}
								className="absolute inset-y-0 right-0 flex items-center px-3 text-sm text-slate-500 transition hover:text-slate-700"
								tabIndex={-1}
							>
								{showPassword ? 'Hide' : 'Show'}
							</button>
						</div>
						{errors.password && (
							<p id="seo-ai-meta-auth-password-error" className="mt-1 text-sm text-rose-600">
								{errors.password}
							</p>
						)}
					</div>

					{mode === 'register' && (
						<div className="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
							<input
								id="seo-ai-meta-auth-marketing"
								type="checkbox"
								className="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
								checked={marketingOptIn}
								onChange={(event) => setMarketingOptIn(event.target.checked)}
								disabled={loading}
							/>
							<label htmlFor="seo-ai-meta-auth-marketing" className="text-sm text-slate-600">
								Send me SEO tips, playbooks, and product updates.
							</label>
						</div>
					)}

					{renderFormDescription()}

					<button
						type="submit"
						className="w-full rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 py-2.5 font-medium text-white transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60"
						disabled={loading}
					>
						<div className="flex items-center justify-center gap-2 text-sm">
							{loading && <Spinner size={16} className="text-white" />}
							{mode === 'login' ? 'Log in securely' : 'Create free account'}
						</div>
					</button>

					{mode === 'register' && (
						<p className="text-xs text-slate-500">
							By continuing you agree to the Terms &amp; Privacy.
						</p>
					)}
				</form>

				<div className="mt-6 text-center text-sm text-slate-600">
					{mode === 'login' ? (
						<p>
							Don&apos;t have an account?{' '}
							<button
								type="button"
								onClick={() => resetForm('register')}
								className="font-semibold text-blue-600 hover:text-blue-500"
							>
								Sign up!
							</button>
						</p>
					) : (
						<p>
							Already a member?{' '}
							<button
								type="button"
								onClick={() => resetForm('login')}
								className="font-semibold text-blue-600 hover:text-blue-500"
							>
								Log in
							</button>
						</p>
					)}
				</div>

				<p className="mt-4 text-center text-[12px] text-slate-500">
					Your account is protected with industry-standard encryption.
				</p>
			</div>

			{toastMessage && (
				<div className="fixed top-5 left-1/2 z-[1300] w-[min(90vw,320px)] -translate-x-1/2 rounded-lg border border-blue-100 bg-white/90 px-4 py-2 shadow-lg backdrop-blur">
					<p className="text-sm font-medium text-slate-700">{toastMessage}</p>
				</div>
			)}
		</div>
	);
};

export default AuthModal;

