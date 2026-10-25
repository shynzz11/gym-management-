<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FilterPaymentRequest;
use App\Http\Requests\Admin\UpdatePaymentStatusRequest;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $paymentService) {}

    public function index(FilterPaymentRequest $request): Response
    {
        Gate::authorize('view_payments');
        $filters = $request->validated();

        return $this->renderPage($filters, route('admin.payments.index'));
    }

    public function pending(FilterPaymentRequest $request): Response
    {
        Gate::authorize('view_pending_payments');
        $filters = array_merge($request->validated(), [
            'status' => 'pending',
        ]);

        return $this->renderPage($filters, route('admin.payments.pending'));
    }

    public function updateStatus(UpdatePaymentStatusRequest $request, Payment $payment): RedirectResponse
    {
        Gate::authorize('view_pending_payments');

        $data = $request->validated();

        try {
            $this->paymentService->updatePendingStatus(
                $payment,
                (string) $data['decision'],
                isset($data['denial_reason']) ? (string) $data['denial_reason'] : null,
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        $successMessage = $data['decision'] === 'approve'
            ? 'Payment approved successfully.'
            : 'Payment denied successfully.';

        return back()->with('success', $successMessage);
    }

    protected function renderPage(array $filters, string $indexUrl): Response
    {
        return inertia('Admin/Payments/Index', [
            'payments' => $this->paymentService->getAll($filters),
            'customers' => User::query()
                ->select('id', 'name')
                ->where('type', UserType::CUSTOMER)
                ->whereHas('subscriptions.payments')
                ->orderBy('name')
                ->get(),
            'plans' => Plan::query()
                ->select('id', 'name')
                ->whereHas('subscriptions.payments')
                ->orderBy('name')
                ->get(),
            'filterOptions' => $this->paymentService->getFilterOptions(),
            'indexUrl' => $indexUrl,
            'filters' => [
                'customer_id' => $filters['customer_id'] ?? null,
                'plan_id' => $filters['plan_id'] ?? null,
                'type' => $filters['type'] ?? null,
                'method' => $filters['method'] ?? null,
                'status' => $filters['status'] ?? null,
            ],
        ]);
    }
}
