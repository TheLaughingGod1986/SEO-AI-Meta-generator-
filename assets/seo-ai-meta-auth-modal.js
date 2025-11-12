(function () {
	if (typeof window.wp === 'undefined' || !window.wp.element) {
		console.warn('wp.element is not available – auth modal will not be initialised.');
		return;
	}

	const {
		createElement: h,
		Fragment,
		useState,
		useEffect,
		useRef,
		useCallback,
		useMemo,
	} = window.wp.element;
	const render =
		window.wp.element.render ||
		(window.ReactDOM && window.ReactDOM.render) ||
		null;

	if (!render) {
		console.warn('React render method is unavailable – auth modal will not be initialised.');
		return;
	}

	const ensureTailwind = () => {
		if (document.getElementById('seo-ai-meta-tailwind')) {
			return Promise.resolve();
		}

		return new Promise((resolve) => {
			// Use Tailwind Play CDN with JIT mode for better compatibility
			const script = document.createElement('script');
			script.id = 'seo-ai-meta-tailwind';
			script.src = 'https://cdn.tailwindcss.com';
			script.onload = () => {
				console.log('SEO AI Meta: Tailwind CSS loaded');
				// Configure Tailwind to avoid conflicts
				if (window.tailwind && window.tailwind.config) {
					window.tailwind.config = {
						corePlugins: {
							preflight: false, // Disable CSS reset to avoid conflicts
						}
					};
				}
				resolve();
			};
			script.onerror = () => {
				console.error('SEO AI Meta: Failed to load Tailwind CSS');
				resolve(); // Still resolve to not block modal
			};
			document.head.appendChild(script);
		});
	};

	const isValidEmail = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test((value || '').trim());

	const validatePassword = (value) => {
		if (!value) {
			return 'Password is required.';
		}
		if (value.length < 8) {
			return 'Password must be at least 8 characters.';
		}
		return null;
	};

	const Spinner = ({ size = 18, className = '' }) =>
		h(
			'svg',
			{
				className: `animate-spin text-white ${className}`,
				width: size,
				height: size,
				viewBox: '0 0 24 24',
				role: 'status',
				'aria-live': 'polite',
			},
			h('title', null, 'Loading'),
			h('circle', {
				className: 'opacity-25',
				cx: '12',
				cy: '12',
				r: '10',
				stroke: 'currentColor',
				strokeWidth: '4',
				fill: 'none',
			}),
			h('path', {
				className: 'opacity-75',
				fill: 'currentColor',
				d: 'M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z',
			})
		);

	const useFocusTrap = (isOpen, containerRef) => {
		useEffect(() => {
			if (!isOpen) {
				return undefined;
			}
			const container = containerRef.current;
			if (!container) {
				return undefined;
			}

			const focusableSelectors = [
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

			const focusable = Array.from(
				container.querySelectorAll(focusableSelectors)
			);

			const first = focusable[0] || container;
			const last = focusable[focusable.length - 1] || container;
			const previouslyFocused = document.activeElement;

			const handleKeyDown = (event) => {
				if (event.key !== 'Tab') {
					return;
				}
				if (focusable.length === 0) {
					event.preventDefault();
					container.focus();
					return;
				}

				if (event.shiftKey) {
					if (document.activeElement === first) {
						event.preventDefault();
						last.focus();
					}
				} else if (document.activeElement === last) {
					event.preventDefault();
					first.focus();
				}
			};

			document.addEventListener('keydown', handleKeyDown);

			window.requestAnimationFrame(() => {
				(first || container).focus();
			});

			return () => {
				document.removeEventListener('keydown', handleKeyDown);
				if (previouslyFocused && previouslyFocused.focus) {
					previouslyFocused.focus();
				}
			};
		}, [isOpen, containerRef]);
	};

	const AuthModal = ({ isOpen, onClose, onSuccess }) => {
		const [mode, setMode] = useState('login');
		const [email, setEmail] = useState('');
		const [password, setPassword] = useState('');
		const [marketingOptIn, setMarketingOptIn] = useState(true);
		const [showPassword, setShowPassword] = useState(false);
		const [errors, setErrors] = useState({});
		const [loading, setLoading] = useState(false);
		const [formError, setFormError] = useState(null);
		const [toastMessage, setToastMessage] = useState(null);

		const containerRef = useRef(null);
		const emailInputRef = useRef(null);

		useFocusTrap(isOpen, containerRef);

		useEffect(() => {
			if (!isOpen) {
				return undefined;
			}

			const handleEsc = (event) => {
				if (event.key === 'Escape') {
					event.preventDefault();
					onClose();
				}
			};

			document.addEventListener('keydown', handleEsc);

			return () => {
				document.removeEventListener('keydown', handleEsc);
			};
		}, [isOpen, onClose]);

		useEffect(() => {
			if (!isOpen) {
				return;
			}

			const timer = setTimeout(() => {
				if (emailInputRef.current) {
					emailInputRef.current.focus();
				}
			}, 10);

			return () => clearTimeout(timer);
		}, [isOpen, mode]);

		useEffect(() => {
			if (!toastMessage) {
				return undefined;
			}
			const timer = setTimeout(() => setToastMessage(null), 2400);
			return () => clearTimeout(timer);
		}, [toastMessage]);

		const resetForm = useCallback(
			(nextMode) => {
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

		const validateForm = useCallback(() => {
			const nextErrors = {};
			if (!isValidEmail(email)) {
				nextErrors.email = 'Please enter a valid email address.';
			}
			const passwordError = validatePassword(password);
			if (passwordError) {
				nextErrors.password = passwordError;
			}
			setErrors(nextErrors);
			return Object.keys(nextErrors).length === 0;
		}, [email, password]);

		const simulateRequest = useCallback((endpoint, payload) => {
			return new Promise((resolve, reject) => {
				setTimeout(() => {
					if (payload.email && String(payload.email).includes('fail')) {
						reject(new Error('Invalid credentials. Try again or reset your password.'));
					} else {
						resolve({ endpoint });
					}
				}, 900);
			});
		}, []);

		const handleSubmit = useCallback(
			async (event) => {
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
					}, 900);
				} catch (error) {
					const message =
						error && error.message
							? error.message
							: 'Invalid credentials. Try again or reset your password.';
					setFormError(message);
					setToastMessage(null);
				} finally {
					setLoading(false);
				}
			},
			[
				email,
				password,
				marketingOptIn,
				mode,
				validateForm,
				simulateRequest,
				onSuccess,
			]
		);

		const handleForgotPassword = useCallback(
			async (event) => {
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
						error && error.message
							? error.message
							: 'Unable to send reset link. Please try again.';
					setFormError(message);
				} finally {
					setLoading(false);
				}
			},
			[email, simulateRequest]
		);

		const tabButtons = useMemo(
			() => [
				{ label: 'Log in', value: 'login' },
				{ label: 'Create account', value: 'register' },
			],
			[]
		);

		if (!isOpen) {
			return toastMessage
				? h(
						Fragment,
						null,
						h(
							'div',
							{
								className:
									'fixed top-5 left-1/2 z-[1300] w-[min(90vw,320px)] -translate-x-1/2 rounded-lg border border-blue-100 bg-white/90 px-4 py-2 text-sm font-medium text-slate-700 shadow-lg backdrop-blur',
							},
							toastMessage
						)
				  )
				: null;
		}

		return h(
			Fragment,
			null,
			h(
				'div',
				{
					className:
						'fixed inset-0 z-[1200] flex items-center justify-center bg-slate-900/40 px-4 py-6',
					style: { pointerEvents: 'auto' },
					onMouseDown: (event) => {
						if (event.target === event.currentTarget) {
							onClose();
						}
					},
				},
				h(
					'div',
					{
						ref: containerRef,
						className:
							'mx-auto w-[92vw] max-w-[420px] rounded-xl border border-slate-200 bg-white p-6 shadow-md focus:outline-none',
						role: 'dialog',
						'aria-modal': 'true',
						'aria-labelledby': 'seo-ai-meta-auth-modal-title',
						'aria-busy': loading ? 'true' : 'false',
						tabIndex: -1,
						onMouseDown: (event) => event.stopPropagation(),
					},
					h(
						'div',
						{ className: 'flex items-start justify-between' },
						h(
							'div',
							{ className: 'flex items-center gap-3' },
							h(
								'div',
								{
									className:
										'flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-white',
								},
								h('span', { className: 'text-sm font-bold tracking-wide' }, 'SO')
							),
							h(
								'div',
								null,
								h(
									'h2',
									{
										id: 'seo-ai-meta-auth-modal-title',
										className: 'text-xl font-semibold text-slate-900',
									},
									'SEO AI Meta'
								),
								h(
									'p',
									{ className: 'text-sm text-slate-500' },
									'Smart SEO titles & meta descriptions powered by AI.'
								)
							)
						),
						h(
							'button',
							{
								type: 'button',
								onClick: onClose,
								className:
									'rounded-full p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600',
								'aria-label': 'Close authentication modal',
							},
							h(
								'svg',
								{
									xmlns: 'http://www.w3.org/2000/svg',
									viewBox: '0 0 24 24',
									fill: 'none',
									stroke: 'currentColor',
									strokeWidth: 2,
									className: 'h-5 w-5',
								},
								h('path', {
									strokeLinecap: 'round',
									strokeLinejoin: 'round',
									d: 'M6 18L18 6M6 6l12 12',
								})
							)
						)
					),
					h(
						'div',
						{ className: 'mt-6' },
						h(
							'div',
							{ className: 'grid grid-cols-2 rounded-lg bg-slate-100 p-1' },
							tabButtons.map((tab) =>
								h(
									'button',
									{
										key: tab.value,
										type: 'button',
										onClick: () => resetForm(tab.value),
										className: `rounded-md px-3 py-2 text-sm font-medium transition ${
											mode === tab.value
												? 'bg-white shadow-sm ring-1 ring-slate-200 text-slate-900'
												: 'text-slate-500 hover:text-slate-700'
										}`,
										'aria-pressed': mode === tab.value ? 'true' : 'false',
									},
									tab.label
								)
							)
						)
					),
					h(
						'form',
						{ className: 'mt-6 space-y-5', onSubmit: handleSubmit },
						formError &&
							h(
								'div',
								{
									className:
										'rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-600',
									role: 'alert',
								},
								formError
							),
						h(
							'div',
							null,
							h(
								'label',
								{
									htmlFor: 'seo-ai-meta-auth-email',
									className: 'block text-sm font-medium text-slate-700',
								},
								'Email'
							),
							h('input', {
								id: 'seo-ai-meta-auth-email',
								ref: emailInputRef,
								type: 'email',
								autoComplete: 'email',
								className: `mt-1 w-full rounded-lg border px-3 py-2 text-[15px] outline-none transition ${
									errors.email
										? 'border-rose-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-300'
										: 'border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500'
								}`,
								value: email,
								onChange: (event) => setEmail(event.target.value),
								'aria-invalid': errors.email ? 'true' : 'false',
								'aria-describedby': errors.email
									? 'seo-ai-meta-auth-email-error'
									: undefined,
								disabled: loading,
							}),
							errors.email &&
								h(
									'p',
									{
										id: 'seo-ai-meta-auth-email-error',
										className: 'mt-1 text-sm text-rose-600',
									},
									errors.email
								)
						),
						h(
							'div',
							null,
							h(
								'div',
								{ className: 'flex items-center justify-between' },
								h(
									'label',
									{
										htmlFor: 'seo-ai-meta-auth-password',
										className: 'block text-sm font-medium text-slate-700',
									},
									'Password'
								),
								mode === 'login' &&
									h(
										'button',
										{
											type: 'button',
											onClick: handleForgotPassword,
											className:
												'text-sm font-medium text-blue-600 transition hover:text-blue-500',
											disabled: loading,
										},
										'Forgot password?'
									)
							),
							h(
								'div',
								{ className: 'relative mt-1' },
								h('input', {
									id: 'seo-ai-meta-auth-password',
									type: showPassword ? 'text' : 'password',
									autoComplete: mode === 'login' ? 'current-password' : 'new-password',
									className: `w-full rounded-lg border px-3 py-2 text-[15px] outline-none transition ${
										errors.password
											? 'border-rose-400 focus:border-rose-500 focus:ring-2 focus:ring-rose-300'
											: 'border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500'
									}`,
									value: password,
									onChange: (event) => setPassword(event.target.value),
									'aria-invalid': errors.password ? 'true' : 'false',
									'aria-describedby': errors.password
										? 'seo-ai-meta-auth-password-error'
										: undefined,
									disabled: loading,
								}),
								h(
									'button',
									{
										type: 'button',
										onClick: () => setShowPassword((prev) => !prev),
										className:
											'absolute inset-y-0 right-0 flex items-center px-3 text-sm text-slate-500 transition hover:text-slate-700',
										tabIndex: -1,
									},
									showPassword ? 'Hide' : 'Show'
								)
							),
							errors.password &&
								h(
									'p',
									{
										id: 'seo-ai-meta-auth-password-error',
										className: 'mt-1 text-sm text-rose-600',
									},
									errors.password
								)
						),
						mode === 'register' &&
							h(
								'div',
								{
									className:
										'flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3',
								},
								h('input', {
									id: 'seo-ai-meta-auth-marketing',
									type: 'checkbox',
									className:
										'mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500',
									checked: marketingOptIn,
									onChange: (event) => setMarketingOptIn(event.target.checked),
									disabled: loading,
								}),
								h(
									'label',
									{
										htmlFor: 'seo-ai-meta-auth-marketing',
										className: 'text-sm text-slate-600',
									},
									'Send me SEO tips, playbooks, and product updates.'
								)
							),
						mode === 'register' &&
							h(
								'p',
								{ className: 'mt-3 text-sm font-medium text-emerald-600' },
								'No card required — includes 10 free AI generations/month.'
							),
						h(
							'button',
							{
								type: 'submit',
								className:
									'w-full rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 py-2.5 font-medium text-white transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60',
								disabled: loading,
							},
							h(
								'div',
								{ className: 'flex items-center justify-center gap-2 text-sm' },
								loading && h(Spinner, { size: 16, className: 'text-white' }),
								mode === 'login' ? 'Log in securely' : 'Create free account'
							)
						),
						mode === 'register' &&
							h(
								'p',
								{ className: 'text-xs text-slate-500' },
								'By continuing you agree to the Terms & Privacy.'
							)
					),
					h(
						'div',
						{ className: 'mt-6 text-center text-sm text-slate-600' },
						mode === 'login'
							? h(
									'p',
									null,
									"Don't have an account? ",
									h(
										'button',
										{
											type: 'button',
											onClick: () => resetForm('register'),
											className: 'font-semibold text-blue-600 hover:text-blue-500',
										},
										'Sign up!'
									)
							  )
							: h(
									'p',
									null,
									'Already a member? ',
									h(
										'button',
										{
											type: 'button',
											onClick: () => resetForm('login'),
											className: 'font-semibold text-blue-600 hover:text-blue-500',
										},
										'Log in'
									)
							  )
					),
					h(
						'p',
						{ className: 'mt-4 text-center text-[12px] text-slate-500' },
						'Your account is protected with industry-standard encryption.'
					)
				)
			),
			toastMessage &&
				h(
					'div',
					{
						className:
							'fixed top-5 left-1/2 z-[1300] w-[min(90vw,320px)] -translate-x-1/2 rounded-lg border border-blue-100 bg-white/90 px-4 py-2 text-sm font-medium text-slate-700 shadow-lg backdrop-blur',
					},
					toastMessage
				)
		);
	};

	const AuthModalApp = () => {
		const [isOpen, setIsOpen] = useState(false);
		const successCallbackRef = useRef(null);

		const closeModal = useCallback(() => setIsOpen(false), []);

		const handleSuccess = useCallback(() => {
			closeModal();
			if (successCallbackRef.current && typeof successCallbackRef.current === 'function') {
				successCallbackRef.current();
				return;
			}
			if (typeof window.seoAiMetaAuthOnSuccess === 'function') {
				window.seoAiMetaAuthOnSuccess();
				return;
			}
			window.location.reload();
		}, [closeModal]);

		useEffect(() => {
			console.log('SEO AI Meta: Registering login modal functions');
			window.seoAiMetaShowLoginModal = () => {
				console.log('SEO AI Meta: seoAiMetaShowLoginModal called');
				setIsOpen(true);
			};
			window.seoAiMetaCloseLoginModal = () => {
				console.log('SEO AI Meta: seoAiMetaCloseLoginModal called');
				closeModal();
			};
			window.seoAiMetaRegisterAuthSuccessCallback = (callback) => {
				successCallbackRef.current = callback;
			};
			console.log('SEO AI Meta: Login modal functions registered:', {
				seoAiMetaShowLoginModal: typeof window.seoAiMetaShowLoginModal,
				seoAiMetaCloseLoginModal: typeof window.seoAiMetaCloseLoginModal
			});
			return () => {
				delete window.seoAiMetaShowLoginModal;
				delete window.seoAiMetaCloseLoginModal;
				delete window.seoAiMetaRegisterAuthSuccessCallback;
			};
		}, [closeModal]);

		return h(AuthModal, {
			isOpen,
			onClose: closeModal,
			onSuccess: handleSuccess,
		});
	};

	const mount = async () => {
		console.log('SEO AI Meta: Auth modal mounting...');

		if (document.getElementById('seo-ai-meta-auth-root')) {
			console.log('SEO AI Meta: Auth modal already mounted');
			return;
		}

		// Wait for Tailwind CSS to load
		await ensureTailwind();

		const container = document.createElement('div');
		container.id = 'seo-ai-meta-auth-root';
		// Ensure container fills viewport and sits at top of stacking context
		container.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 9999;';
		document.body.appendChild(container);

		render(h(AuthModalApp, null), container);
		console.log('SEO AI Meta: Auth modal mounted successfully');
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', mount);
	} else {
		mount();
	}
})();

