{{-- Delivery Table Partial --}}
<div class="table-responsive mb-4">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Address</th>
                <th>Products/Notes</th>
                <th>Contact</th>
                <th>Frequency</th>
                <th>Week</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $delivery)
                <tr>
                    <td>
                        <strong>{{ $delivery['name'] ?? 'N/A' }}</strong>
                        @if(isset($delivery['id']))
                            <br><small class="text-muted">ID: {{ $delivery['id'] }}</small>
                        @endif
                    </td>
                    <td>
                        @if(isset($delivery['address']) && is_array($delivery['address']) && !empty(array_filter($delivery['address'])))
                            @foreach($delivery['address'] as $addressLine)
                                @if(!empty($addressLine))
                                    {{ $addressLine }}<br>
                                @endif
                            @endforeach
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if(!empty($delivery['products']) && is_array($delivery['products']))
                            @foreach($delivery['products'] as $product)
                                {{ $product['name'] ?? 'Product' }} ({{ $product['quantity'] ?? 1 }})<br>
                            @endforeach
                        @else
                            <small class="text-muted">No product details</small>
                        @endif
                    </td>
                    <td>
                        @if(!empty($delivery['phone']))
                            <i class="fas fa-phone"></i> {{ $delivery['phone'] }}<br>
                        @endif
                        @if(!empty($delivery['email']))
                            <i class="fas fa-envelope"></i> {{ $delivery['email'] }}
                        @endif
                    </td>
                    <td>
                        @if(isset($delivery['frequency']))
                            <span class="badge bg-{{ $delivery['frequency_badge'] ?? 'warning' }}">
                                {{ $delivery['frequency'] }}
                            </span>
                        @else
                            <span class="badge bg-primary">
                                Weekly
                            </span>
                        @endif
                    </td>
                    <td>
                        @if(isset($delivery['customer_week_type']) && $delivery['customer_week_type'] !== 'Weekly')
                            <div class="dropdown">
                                <button class="btn btn-sm badge bg-{{ $delivery['week_badge'] ?? 'secondary' }} dropdown-toggle" 
                                        type="button" 
                                        data-bs-toggle="dropdown" 
                                        aria-expanded="false"
                                        style="border: none;">
                                    Week {{ $delivery['customer_week_type'] }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item week-change-btn" 
                                           href="#" 
                                           data-customer-id="{{ $delivery['id'] }}"
                                           data-current-week="{{ $delivery['customer_week_type'] }}"
                                           data-new-week="A">
                                        <span class="badge bg-success me-2">A</span>Week A (Odd weeks)
                                    </a></li>
                                    <li><a class="dropdown-item week-change-btn" 
                                           href="#" 
                                           data-customer-id="{{ $delivery['id'] }}"
                                           data-current-week="{{ $delivery['customer_week_type'] }}"
                                           data-new-week="B">
                                        <span class="badge bg-info me-2">B</span>Week B (Even weeks)
                                    </a></li>
                                </ul>
                            </div>
                            <br><small class="text-muted">
                                Current: Week {{ $delivery['current_week_type'] ?? '?' }}
                                @if(isset($delivery['should_deliver_this_week']))
                                    | {{ $delivery['should_deliver_this_week'] ? '✅ Active' : '⏸️ Skip' }} this week
                                @endif
                            </small>
                        @else
                            <span class="badge bg-primary">Every Week</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $delivery['status'] === 'processing' ? 'warning' : ($delivery['status'] === 'completed' ? 'success' : 'secondary') }}">
                            {{ ucfirst($delivery['status'] ?? 'pending') }}
                        </span>
                    </td>
                    <td>
                        @if(!empty($delivery['customer_email']))
                            <button class="btn btn-sm btn-outline-primary user-switch-btn" 
                                    data-email="{{ $delivery['customer_email'] }}" 
                                    data-name="{{ $delivery['customer_name'] ?? 'Customer' }}"
                                    title="Switch to this user's account">
                                <i class="fas fa-user-circle"></i> Switch to User
                            </button>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
