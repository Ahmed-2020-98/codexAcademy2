<?php

namespace App\Livewire;

use App\Models\Course;
use App\Services\Checkout\CheckoutService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.learn')]
class Checkout extends Component
{
    public Course $course;

    public string $code = '';

    public ?string $appliedCode = null;

    public function mount(Course $course): void
    {
        abort_unless($course->is_published, 404);

        // Live/offline seats are confirmed by the sales team after the level test.
        if ($course->type->isInteractive()) {
            $this->redirectRoute('register', ['course' => $course->slug]);

            return;
        }

        if (auth()->user()->isEnrolledIn($course)) {
            $this->redirectRoute('learn.course', $course);
        }
    }

    public function applyCode(CheckoutService $checkout): void
    {
        $this->resetErrorBag('code');

        try {
            $checkout->quote(auth()->user(), $this->course, $this->code);
            $this->appliedCode = strtoupper(trim($this->code));
        } catch (ValidationException $e) {
            $this->appliedCode = null;
            $this->addError('code', $e->validator->errors()->first());
        }
    }

    public function removeCode(): void
    {
        $this->reset('code', 'appliedCode');
    }

    public function pay(CheckoutService $checkout): void
    {
        try {
            $checkout->purchase(auth()->user(), $this->course, $this->appliedCode);
        } catch (ValidationException $e) {
            $this->addError('payment', $e->validator->errors()->first());

            return;
        }

        session()->flash('welcome', $this->course->title);
        $this->redirectRoute('learn.course', $this->course);
    }

    public function render(CheckoutService $checkout)
    {
        return view('livewire.checkout', [
            'quote' => $checkout->quote(auth()->user(), $this->course, $this->appliedCode),
        ])->title('إتمام الشراء');
    }
}
