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
                        <strong>{{ $delivery['customer_name'] ?? 'N/A' }}</strong>
                        @if(isset($delivery['order_number']))
                            <br><small class="text-muted">ID: {{ $delivery['order_number'] }}</small>
                        @endif
                    </td>
                    <td>
                        @if(isset($delivery['shipping_address']) && is_array($delivery['shipping_address']))
                            {{ $delivery['shipping_address']['first_name'] ?? '' }} {{ $delivery['shipping_address']['last_name'] ?? '' }}<br>
                            @if(!empty($delivery['shipping_address']['address_1']))
                                {{ $delivery['shipping_address']['address_1'] }}<br>
                            @endif
                            @if(!empty($delivery['shipping_address']['address_2']))
                                {{ $delivery['shipping_address']['address_2'] }}<br>
                            @endif
                            @if(!empty($delivery['shipping_address']['city']))
                                {{ $delivery['shipping_address']['city'] }}<br>
                            @endif
                            @if(!empty($delivery['shipping_address']['postcode']))
                                {{ $delivery['shipping_address']['postcode'] }}
                            @endif
                        @elseif(isset($delivery['billing_address']) && is_array($delivery['billing_address']))
                            {{ $delivery['billing_address']['first_name'] ?? '' }} {{ $delivery['billing_address']['last_name'] ?? '' }}<br>
                            @if(!empty($delivery['billing_address']['address_1']))
                                {{ $delivery['billing_address']['address_1'] }}<br>
                            @endif
                            @if(!empty($delivery['billing_address']['address_2']))
                                {{ $delivery['billing_address']['address_2'] }}<br>
                            @endif
                            @if(!empty($delivery['billing_address']['city']))
                                {{ $delivery['billing_address']['city'] }}<br>
                            @endif
                            @if(!empty($delivery['billing_address']['postcode']))
                                {{ $delivery['billing_address']['postcode'] }}
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if(!empty($delivery['special_instructions']) || !empty($delivery['delivery_notes']))
                            {{ $delivery['special_instructions'] ?? $delivery['delivery_notes'] ?? 'N/A' }}
                        @else
                            <small class="text-muted">£{{ number_format($delivery['total'] ?? 0, 2) }}</small>
                        @endif
                    </td>
                    <td>
                        @if(!empty($delivery['billing_address']['phone']))
                            <i class="fas fa-phone"></i> {{ $delivery['billing_address']['phone'] }}<br>
                        @endif
                        @if(!empty($delivery['customer_email']))
                            <i class="fas fa-envelope"></i> {{ $delivery['customer_email'] }}
                        @endif
                    </td>
                    <td>
                        @if(isset($delivery['frequency']))
                            <span class="badge bg-{{ $delivery['frequency_badge'] ?? 'warning' }}">
                                {{ $delivery['frequency'] }}
                            </span>
                        @else
                            <span class="badge bg-{{ $delivery['type'] === 'order' ? 'warning' : 'primary' }}">
                                {{ $delivery['type'] === 'order' ? 'One-time Delivery' : 'Weekly' }}
                            </span>
                        @endif
                    </td>
                    <td>
                        @if(isset($delivery['customer_week_type']) && $delivery['customer_week_type'] !== 'Weekly')
                            <span class="badge bg-{{ $delivery['week_badge'] ?? 'secondary' }}">
                                Week {{ $delivery['customer_week_type'] }}
                            </span>
                        @else
                            <span class="badge bg-primary">One-time</span>
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
