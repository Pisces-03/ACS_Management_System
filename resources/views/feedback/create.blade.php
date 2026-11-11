@extends('layouts.app')

@section('title', 'Submit Customer Evaluation')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-emoji-smile"></i> Customer Team Evaluation</h2>
                <a href="{{ route('feedback.my') }}" class="btn btn-secondary"><i class="bi bi-clock-history"></i> My Evaluations</a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Evaluation Form</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('feedback.store') }}" id="feedbackForm">
                        @csrf
                        @if(isset($task))
                            <input type="hidden" name="task_id" value="{{ $task->id }}">
                        @endif
                        @if(isset($maintenance))
                            <input type="hidden" name="maintenance_log_id" value="{{ $maintenance->id }}">
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('customer_name') is-invalid @enderror" name="customer_name" value="{{ old('customer_name') }}" required>
                            @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Customer Contact</label>
                            <input type="tel" class="form-control @error('customer_contact') is-invalid @enderror" 
                                   name="customer_contact" 
                                   value="{{ old('customer_contact') }}" 
                                   placeholder="09123456789 or +6391234567"
                                   pattern="^(\+63\d{10}|\d{11})$"
                                   minlength="11"
                                   title="Enter 11 digits (e.g., 09123456789) or +63 followed by 10 digits (e.g., +6391234567)">
                            <small class="form-text text-muted">Enter 11 digits (e.g., 09123456789) or +63 followed by 10 digits (e.g., +6391234567)</small>
                            @error('customer_contact')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Overall Rating <span class="text-danger">*</span></label>
                            <select class="form-select @error('rating') is-invalid @enderror" name="rating" required>
                                <option value="">Select Rating</option>
                                @for($i=5;$i>=1;$i--)
                                    <option value="{{ $i }}" {{ old('rating') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                            @error('rating')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Quality</label>
                                <select class="form-select" name="service_aspects[quality]">
                                    <option value="">N/A</option>
                                    @for($i=5;$i>=1;$i--)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Timeliness</label>
                                <select class="form-select" name="service_aspects[timeliness]">
                                    <option value="">N/A</option>
                                    @for($i=5;$i>=1;$i--)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Professionalism</label>
                                <select class="form-select" name="service_aspects[professionalism]">
                                    <option value="">N/A</option>
                                    @for($i=5;$i>=1;$i--)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label">Would Recommend</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="would_recommend" value="1" checked>
                                    <label class="form-check-label">Yes</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="would_recommend" value="0">
                                    <label class="form-check-label">No</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Comments</label>
                            <textarea class="form-control" name="feedback" rows="4" placeholder="Additional comments or details...">{{ old('feedback') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Suggestions</label>
                            <textarea class="form-control" name="suggestions" rows="3" placeholder="Any suggestions for improvement?">{{ old('suggestions') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ url()->previous() }}" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Submit Evaluation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactInput = document.querySelector('input[name="customer_contact"]');
    const feedbackForm = document.getElementById('feedbackForm');
    
    if (contactInput) {
        // Handle input to clean and limit
        contactInput.addEventListener('input', function(e) {
            let value = e.target.value;
            const cursorPos = e.target.selectionStart;
            
            // Remove any characters that are not digits or +
            value = value.replace(/[^\d+]/g, '');
            
            // If it starts with +63, allow max 13 characters (digits only after +63)
            if (value.startsWith('+63')) {
                // Ensure only digits come after +63
                const afterPlus63 = value.substring(3).replace(/\D/g, '');
                value = '+63' + afterPlus63;
                // Limit to 13 characters total (+63 + 10 digits = 13)
                if (value.length > 13) {
                    value = value.substring(0, 13);
                }
            } 
            // If it starts with + but not +63 yet, allow it (user might be typing +63)
            else if (value.startsWith('+')) {
                // If it's just + or +6 or +63, keep it and allow up to 13 chars
                if (value.length <= 3) {
                    // Keep as is, will be validated when they finish typing
                } 
                // If it's + but not +63 and has more than 3 chars, remove + and treat as regular number
                else if (!value.startsWith('+63')) {
                    value = value.replace(/\+/g, '');
                    // Limit to 11 digits
                    if (value.length > 11) {
                        value = value.substring(0, 11);
                    }
                }
            }
            // If it starts with 09 or any digit, allow max 11 digits only
            else if (/^\d/.test(value)) {
                // Remove any + signs if it's a regular number
                value = value.replace(/\+/g, '');
                // Limit to 11 digits
                if (value.length > 11) {
                    value = value.substring(0, 11);
                }
            }
            // If it's empty, allow it
            else if (value === '') {
                // Keep empty
            }
            
            // Update the input value
            if (value !== e.target.value) {
                e.target.value = value;
                // Restore cursor position
                const newCursorPos = Math.min(cursorPos, value.length);
                e.target.setSelectionRange(newCursorPos, newCursorPos);
            }
            
            // Validate format
            const phonePattern = /^(\+63\d{10}|\d{11})$/;
            if (value.length > 0 && !phonePattern.test(value)) {
                e.target.setCustomValidity('Enter 11 digits (e.g., 09123456789) or +63 followed by 10 digits (e.g., +6391234567)');
            } else {
                e.target.setCustomValidity('');
            }
        });
        
        // Handle paste to clean pasted content
        contactInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            let cleaned = pastedText.replace(/[^\d+]/g, '');
            
            // Apply same logic as input handler
            if (cleaned.startsWith('+63')) {
                const afterPlus63 = cleaned.substring(3).replace(/\D/g, '');
                cleaned = '+63' + afterPlus63;
                if (cleaned.length > 13) cleaned = cleaned.substring(0, 13);
            } else if (cleaned.startsWith('+') && !cleaned.startsWith('+63')) {
                cleaned = cleaned.replace(/\+/g, '');
            } else if (/^\d/.test(cleaned)) {
                cleaned = cleaned.replace(/\+/g, '');
                if (cleaned.length > 11) cleaned = cleaned.substring(0, 11);
            } else {
                cleaned = cleaned.replace(/\+/g, '');
            }
            
            e.target.value = cleaned;
            e.target.dispatchEvent(new Event('input'));
        });
        
        // Validate on form submit
        if (feedbackForm) {
            feedbackForm.addEventListener('submit', function(e) {
                const value = contactInput.value.trim();
                
                if (value && value.length > 0) {
                    const phonePattern = /^(\+63\d{10}|\d{11})$/;
                    if (!phonePattern.test(value)) {
                        e.preventDefault();
                        contactInput.focus();
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid Contact Number',
                            text: 'Please enter 11 digits (e.g., 09123456789) or +63 followed by 10 digits (e.g., +6391234567)',
                        });
                        return false;
                    }
                }
            });
        }
    }
});
</script>
@endpush


