{{-- Collection Table Partial --}}
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
                <th>Next Payment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $collection)
                <tr>
                    <td>
                        <strong>{{ $collection['customer_name'] ?? 'N/A' }}</strong>
                        @if(isset($collection['order_number']))
                            <br><small class="text-muted">ID: {{ $collection['order_number'] }}</small>
                        @endif
                    </td>
                    <td>
                        @if(isset($collection['shipping_address']) && is_array($collection['shipping_address']))
                            {{ $collection['shipping_address']['first_name'] ?? '' }} {{ $collection['shipping_address']['last_name'] ?? '' }}<br>
                            @if(!empty($collection['shipping_address']['address_1']))
                                {{ $collection['shipping_address']['address_1'] }}<br>
                            @endif
                            @if(!empty($collection['shipping_address']['address_2']))
                                {{ $collection['shipping_address']['address_2'] }}<br>
                            @endif
                            @if(!empty($collection['shipping_address']['city']))
                                {{ $collection['shipping_address']['city'] }}<br>
                            @endif
                            @if(!empty($collection['shipping_address']['postcode']))
                                {{ $collection['shipping_address']['postcode'] }}
                            @endif
                        @elseif(isset($collection['billing_address']) && is_array($collection['billing_address']))
                            {{ $collection['billing_address']['first_name'] ?? '' }} {{ $collection['billing_address']['last_name'] ?? '' }}<br>
                            @if(!empty($collection['billing_address']['address_1']))
                                {{ $collection['billing_address']['address_1'] }}<br>
                            @endif
                            @if(!empty($collection['billing_address']['address_2']))
                                {{ $collection['billing_address']['address_2'] }}<br>
                            @endif
                            @if(!empty($collection['billing_address']['city']))
                                {{ $collection['billing_address']['city'] }}<br>
                            @endif
                            @if(!empty($collection['billing_address']['postcode']))
                                {{ $collection['billing_address']['postcode'] }}
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if(!empty($collection['special_instructions']) || !empty($collection['delivery_notes']))
                            {{ $collection['special_instructions'] ?? $collection['delivery_notes'] ?? 'N/A' }}
                        @else
                            <small class="text-muted">£{{ number_format($collection['total'] ?? 0, 2) }}</small>
                        @endif
                    </td>
                    <td>
                        @if(!empty($collection['billing_address']['phone']))
                            <i class="fas fa-phone"></i> {{ $collection['billing_address']['phone'] }}<br>
                        @endif
                        @if(!empty($collection['customer_email']))
                            <i class="fas fa-envelope"></i> {{ $collection['customer_email'] }}
                        @endif
                    </td>
                    <td>
                        @if(isset($collection['frequency']))
                            <span class="badge bg-{{ $collection['frequency_badge'] ?? 'secondary' }}">
                                {{ $collection['frequency'] }}
                            </span>
                            @if(strtolower($collection['frequency']) === 'fortnightly')
                                <br><small class="text-muted">
                                    @if(isset($collection['should_deliver_this_week']))
                                        {{ $collection['should_deliver_this_week'] ? '✅ Active' : '⏸️ Skip' }} this week
                                    @endif
                                </small>
                            @endif
                        @else
                            <span class="badge bg-{{ $collection['type'] === 'subscription' ? 'success' : 'info' }}">
                                {{ $collection['type'] === 'subscription' ? 'Weekly Collection' : 'One-time' }}
                            </span>
                        @endif
                    </td>
                    <td>
                        @if(isset($collection['customer_week_type']) && $collection['customer_week_type'] !== 'Weekly')
                            <span class="badge bg-{{ $collection['week_badge'] ?? 'secondary' }}">
                                Week {{ $collection['customer_week_type'] }}
                            </span>
                            <br><small class="text-muted">
                                (Current: Week {{ $collection['current_week_type'] ?? '?' }})
                            </small>
                        @else
                            <span class="badge bg-primary">Every Week</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ isset($collection['status']) && $collection['status'] === 'active' ? 'success' : 'warning' }}">
                            {{ ucfirst($collection['status'] ?? 'pending') }}
                        </span>
                    </td>
                    <td>
                        @if(isset($collection['next_payment']))
                            @if(is_numeric($collection['next_payment']) && $collection['next_payment'] == 0)
                                <span class="text-warning">Pending</span>
                            @else
                                <small>{{ date('Y-m-d', strtotime($collection['next_payment'])) }}</small>
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if(!empty($collection['customer_email']))
                            <button class="btn btn-sm btn-outline-primary user-switch-btn" 
                                    data-email="{{ $collection['customer_email'] }}" 
                                    data-name="{{ $collection['customer_name'] ?? 'Customer' }}"
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
