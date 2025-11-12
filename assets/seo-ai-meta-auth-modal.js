/**
 * SEO AI Meta Generator - Authentication Modal
 * React-based login/register modal for account-based authentication
 */

(function() {
    'use strict';

    const { createElement: e, useState, useEffect } = wp.element;

    /**
     * Authentication Modal Component
     */
    function AuthModal() {
        const [activeTab, setActiveTab] = useState('login');
        const [isLoading, setIsLoading] = useState(false);
        const [error, setError] = useState('');
        const [success, setSuccess] = useState('');

        // Login form state
        const [loginEmail, setLoginEmail] = useState('');
        const [loginPassword, setLoginPassword] = useState('');

        // Register form state
        const [registerEmail, setRegisterEmail] = useState('');
        const [registerPassword, setRegisterPassword] = useState('');
        const [registerConfirmPassword, setRegisterConfirmPassword] = useState('');

        // Forgot password form state
        const [forgotEmail, setForgotEmail] = useState('');

        /**
         * Handle Login
         */
        const handleLogin = async (e) => {
            e.preventDefault();
            setError('');
            setSuccess('');
            setIsLoading(true);

            if (!loginEmail || !loginPassword) {
                setError('Please enter both email and password.');
                setIsLoading(false);
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'seo_ai_meta_login');
                formData.append('nonce', seoAiMetaAuth.nonce);
                formData.append('email', loginEmail);
                formData.append('password', loginPassword);

                const response = await fetch(seoAiMetaAuth.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    setSuccess('Login successful! Refreshing page...');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    setError(data.data?.message || 'Login failed. Please check your credentials.');
                }
            } catch (err) {
                setError('An error occurred. Please try again.');
            } finally {
                setIsLoading(false);
            }
        };

        /**
         * Handle Register
         */
        const handleRegister = async (e) => {
            e.preventDefault();
            setError('');
            setSuccess('');
            setIsLoading(true);

            if (!registerEmail || !registerPassword || !registerConfirmPassword) {
                setError('Please fill in all fields.');
                setIsLoading(false);
                return;
            }

            if (registerPassword !== registerConfirmPassword) {
                setError('Passwords do not match.');
                setIsLoading(false);
                return;
            }

            if (registerPassword.length < 8) {
                setError('Password must be at least 8 characters long.');
                setIsLoading(false);
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'seo_ai_meta_register');
                formData.append('nonce', seoAiMetaAuth.nonce);
                formData.append('email', registerEmail);
                formData.append('password', registerPassword);

                const response = await fetch(seoAiMetaAuth.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    setSuccess('Registration successful! Logging you in...');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    setError(data.data?.message || 'Registration failed. Please try again.');
                }
            } catch (err) {
                setError('An error occurred. Please try again.');
            } finally {
                setIsLoading(false);
            }
        };

        /**
         * Handle Forgot Password
         */
        const handleForgotPassword = async (e) => {
            e.preventDefault();
            setError('');
            setSuccess('');
            setIsLoading(true);

            if (!forgotEmail) {
                setError('Please enter your email address.');
                setIsLoading(false);
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'seo_ai_meta_forgot_password');
                formData.append('nonce', seoAiMetaAuth.nonce);
                formData.append('email', forgotEmail);

                const response = await fetch(seoAiMetaAuth.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    setSuccess('Password reset link sent! Please check your email.');
                    setForgotEmail('');
                } else {
                    setError(data.data?.message || 'Failed to send reset link. Please try again.');
                }
            } catch (err) {
                setError('An error occurred. Please try again.');
            } finally {
                setIsLoading(false);
            }
        };

        /**
         * Close Modal
         */
        const closeModal = () => {
            const modal = document.getElementById('seo-ai-meta-login-modal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        };

        /**
         * Switch Tab
         */
        const switchTab = (tab) => {
            setActiveTab(tab);
            setError('');
            setSuccess('');
        };

        return e('div', {
            className: 'seo-ai-meta-auth-modal-content',
            style: {
                background: 'white',
                borderRadius: '12px',
                maxWidth: '440px',
                width: '90%',
                padding: '32px',
                position: 'relative',
                boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)'
            }
        },
            // Close Button
            e('button', {
                onClick: closeModal,
                style: {
                    position: 'absolute',
                    top: '16px',
                    right: '16px',
                    background: 'none',
                    border: 'none',
                    fontSize: '24px',
                    cursor: 'pointer',
                    color: '#9ca3af',
                    padding: '4px',
                    lineHeight: '1'
                }
            }, '×'),

            // Title
            e('h2', {
                style: {
                    margin: '0 0 8px 0',
                    fontSize: '24px',
                    fontWeight: '700',
                    color: '#111827'
                }
            }, 'Welcome to SEO AI Meta'),

            e('p', {
                style: {
                    margin: '0 0 24px 0',
                    fontSize: '14px',
                    color: '#6b7280'
                }
            }, 'Sign in to manage your subscription and access AI-powered meta generation'),

            // Tabs
            e('div', {
                style: {
                    display: 'flex',
                    gap: '8px',
                    marginBottom: '24px',
                    borderBottom: '2px solid #e5e7eb'
                }
            },
                e('button', {
                    onClick: () => switchTab('login'),
                    style: {
                        flex: 1,
                        padding: '12px',
                        background: 'none',
                        border: 'none',
                        borderBottom: activeTab === 'login' ? '2px solid #8b5cf6' : '2px solid transparent',
                        marginBottom: '-2px',
                        cursor: 'pointer',
                        fontSize: '14px',
                        fontWeight: '600',
                        color: activeTab === 'login' ? '#8b5cf6' : '#6b7280',
                        transition: 'all 0.2s'
                    }
                }, 'Login'),
                e('button', {
                    onClick: () => switchTab('register'),
                    style: {
                        flex: 1,
                        padding: '12px',
                        background: 'none',
                        border: 'none',
                        borderBottom: activeTab === 'register' ? '2px solid #8b5cf6' : '2px solid transparent',
                        marginBottom: '-2px',
                        cursor: 'pointer',
                        fontSize: '14px',
                        fontWeight: '600',
                        color: activeTab === 'register' ? '#8b5cf6' : '#6b7280',
                        transition: 'all 0.2s'
                    }
                }, 'Register')
            ),

            // Error/Success Messages
            error && e('div', {
                style: {
                    padding: '12px',
                    marginBottom: '16px',
                    background: '#fee2e2',
                    border: '1px solid #fecaca',
                    borderRadius: '6px',
                    color: '#991b1b',
                    fontSize: '14px'
                }
            }, error),

            success && e('div', {
                style: {
                    padding: '12px',
                    marginBottom: '16px',
                    background: '#d1fae5',
                    border: '1px solid #a7f3d0',
                    borderRadius: '6px',
                    color: '#065f46',
                    fontSize: '14px'
                }
            }, success),

            // Login Form
            activeTab === 'login' && e('form', {
                onSubmit: handleLogin
            },
                e('div', { style: { marginBottom: '16px' } },
                    e('label', {
                        style: {
                            display: 'block',
                            marginBottom: '6px',
                            fontSize: '14px',
                            fontWeight: '500',
                            color: '#374151'
                        }
                    }, 'Email'),
                    e('input', {
                        type: 'email',
                        value: loginEmail,
                        onChange: (e) => setLoginEmail(e.target.value),
                        required: true,
                        disabled: isLoading,
                        style: {
                            width: '100%',
                            padding: '10px 12px',
                            border: '1px solid #d1d5db',
                            borderRadius: '6px',
                            fontSize: '14px',
                            boxSizing: 'border-box'
                        },
                        placeholder: 'your@email.com'
                    })
                ),
                e('div', { style: { marginBottom: '16px' } },
                    e('label', {
                        style: {
                            display: 'block',
                            marginBottom: '6px',
                            fontSize: '14px',
                            fontWeight: '500',
                            color: '#374151'
                        }
                    }, 'Password'),
                    e('input', {
                        type: 'password',
                        value: loginPassword,
                        onChange: (e) => setLoginPassword(e.target.value),
                        required: true,
                        disabled: isLoading,
                        style: {
                            width: '100%',
                            padding: '10px 12px',
                            border: '1px solid #d1d5db',
                            borderRadius: '6px',
                            fontSize: '14px',
                            boxSizing: 'border-box'
                        },
                        placeholder: '••••••••'
                    })
                ),
                e('div', {
                    style: {
                        marginBottom: '16px',
                        textAlign: 'right'
                    }
                },
                    e('button', {
                        type: 'button',
                        onClick: () => switchTab('forgot'),
                        style: {
                            background: 'none',
                            border: 'none',
                            color: '#8b5cf6',
                            fontSize: '13px',
                            cursor: 'pointer',
                            padding: '0',
                            textDecoration: 'underline'
                        }
                    }, 'Forgot password?')
                ),
                e('button', {
                    type: 'submit',
                    disabled: isLoading,
                    style: {
                        width: '100%',
                        padding: '12px',
                        background: isLoading ? '#d1d5db' : '#8b5cf6',
                        color: 'white',
                        border: 'none',
                        borderRadius: '6px',
                        fontSize: '14px',
                        fontWeight: '600',
                        cursor: isLoading ? 'not-allowed' : 'pointer',
                        transition: 'background 0.2s'
                    }
                }, isLoading ? 'Logging in...' : 'Login')
            ),

            // Register Form
            activeTab === 'register' && e('form', {
                onSubmit: handleRegister
            },
                e('div', { style: { marginBottom: '16px' } },
                    e('label', {
                        style: {
                            display: 'block',
                            marginBottom: '6px',
                            fontSize: '14px',
                            fontWeight: '500',
                            color: '#374151'
                        }
                    }, 'Email'),
                    e('input', {
                        type: 'email',
                        value: registerEmail,
                        onChange: (e) => setRegisterEmail(e.target.value),
                        required: true,
                        disabled: isLoading,
                        style: {
                            width: '100%',
                            padding: '10px 12px',
                            border: '1px solid #d1d5db',
                            borderRadius: '6px',
                            fontSize: '14px',
                            boxSizing: 'border-box'
                        },
                        placeholder: 'your@email.com'
                    })
                ),
                e('div', { style: { marginBottom: '16px' } },
                    e('label', {
                        style: {
                            display: 'block',
                            marginBottom: '6px',
                            fontSize: '14px',
                            fontWeight: '500',
                            color: '#374151'
                        }
                    }, 'Password'),
                    e('input', {
                        type: 'password',
                        value: registerPassword,
                        onChange: (e) => setRegisterPassword(e.target.value),
                        required: true,
                        disabled: isLoading,
                        style: {
                            width: '100%',
                            padding: '10px 12px',
                            border: '1px solid #d1d5db',
                            borderRadius: '6px',
                            fontSize: '14px',
                            boxSizing: 'border-box'
                        },
                        placeholder: '••••••••'
                    })
                ),
                e('div', { style: { marginBottom: '16px' } },
                    e('label', {
                        style: {
                            display: 'block',
                            marginBottom: '6px',
                            fontSize: '14px',
                            fontWeight: '500',
                            color: '#374151'
                        }
                    }, 'Confirm Password'),
                    e('input', {
                        type: 'password',
                        value: registerConfirmPassword,
                        onChange: (e) => setRegisterConfirmPassword(e.target.value),
                        required: true,
                        disabled: isLoading,
                        style: {
                            width: '100%',
                            padding: '10px 12px',
                            border: '1px solid #d1d5db',
                            borderRadius: '6px',
                            fontSize: '14px',
                            boxSizing: 'border-box'
                        },
                        placeholder: '••••••••'
                    })
                ),
                e('button', {
                    type: 'submit',
                    disabled: isLoading,
                    style: {
                        width: '100%',
                        padding: '12px',
                        background: isLoading ? '#d1d5db' : '#8b5cf6',
                        color: 'white',
                        border: 'none',
                        borderRadius: '6px',
                        fontSize: '14px',
                        fontWeight: '600',
                        cursor: isLoading ? 'not-allowed' : 'pointer',
                        transition: 'background 0.2s'
                    }
                }, isLoading ? 'Creating Account...' : 'Create Account')
            ),

            // Forgot Password Form
            activeTab === 'forgot' && e('div', {},
                e('p', {
                    style: {
                        marginBottom: '16px',
                        fontSize: '14px',
                        color: '#6b7280'
                    }
                }, 'Enter your email address and we\'ll send you a password reset link.'),
                e('form', {
                    onSubmit: handleForgotPassword
                },
                    e('div', { style: { marginBottom: '16px' } },
                        e('label', {
                            style: {
                                display: 'block',
                                marginBottom: '6px',
                                fontSize: '14px',
                                fontWeight: '500',
                                color: '#374151'
                            }
                        }, 'Email'),
                        e('input', {
                            type: 'email',
                            value: forgotEmail,
                            onChange: (e) => setForgotEmail(e.target.value),
                            required: true,
                            disabled: isLoading,
                            style: {
                                width: '100%',
                                padding: '10px 12px',
                                border: '1px solid #d1d5db',
                                borderRadius: '6px',
                                fontSize: '14px',
                                boxSizing: 'border-box'
                            },
                            placeholder: 'your@email.com'
                        })
                    ),
                    e('button', {
                        type: 'submit',
                        disabled: isLoading,
                        style: {
                            width: '100%',
                            padding: '12px',
                            background: isLoading ? '#d1d5db' : '#8b5cf6',
                            color: 'white',
                            border: 'none',
                            borderRadius: '6px',
                            fontSize: '14px',
                            fontWeight: '600',
                            cursor: isLoading ? 'not-allowed' : 'pointer',
                            transition: 'background 0.2s',
                            marginBottom: '12px'
                        }
                    }, isLoading ? 'Sending...' : 'Send Reset Link'),
                    e('button', {
                        type: 'button',
                        onClick: () => switchTab('login'),
                        style: {
                            width: '100%',
                            padding: '12px',
                            background: 'white',
                            color: '#8b5cf6',
                            border: '1px solid #8b5cf6',
                            borderRadius: '6px',
                            fontSize: '14px',
                            fontWeight: '600',
                            cursor: 'pointer',
                            transition: 'all 0.2s'
                        }
                    }, 'Back to Login')
                )
            )
        );
    }

    /**
     * Initialize Modal on DOM Ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        const modalContainer = document.getElementById('seo-ai-meta-login-modal');
        if (modalContainer) {
            const rootDiv = document.createElement('div');
            rootDiv.id = 'seo-ai-meta-auth-root';
            modalContainer.appendChild(rootDiv);

            wp.element.render(
                e(AuthModal),
                rootDiv
            );
        }
    });

    /**
     * Global function to show login modal
     */
    window.seoAiMetaShowLoginModal = function() {
        const modal = document.getElementById('seo-ai-meta-login-modal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    };

})();
