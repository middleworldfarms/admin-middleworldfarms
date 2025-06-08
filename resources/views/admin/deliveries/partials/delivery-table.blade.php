{{-- Delivery Table Partial --}}
<div class="table-responsive mb-4">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Address</th>
                <th>Products</th>
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
                        @if(isset($delivery['address']) && is_array($delivery['address']))
                            @foreach($delivery['address'] as $line)
                                @if(!empty($line))
                                    {{ $line }}<br>
                                @endif
                            @endforeach
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if(isset($delivery['products']) && is_array($delivery['products']))
                            @foreach($delivery['products'] as $product)
                                <div class="mb-1">
                                    <strong>{{ $product['name'] ?? 'Product' }}</strong>
                                    @if(isset($product['quantity']))
                                        <span class="badge bg-secondary">{{ $product['quantity'] }}</span>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if(isset($delivery['phone']) && !empty($delivery['phone']))
                            <i class="fas fa-phone"></i> {{ $delivery['phone'] }}<br>
                        @endif
                        @if(isset($delivery['email']) && !empty($delivery['email']))
                            <i class="fas fa-envelope"></i> {{ $delivery['email'] }}
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $delivery['frequency_badge'] ?? 'secondary' }}">
                            {{ $delivery['frequency'] ?? 'Weekly' }}
                        </span>
                    </td>
                    <td>
                        @if(isset($delivery['frequency']) && strtolower($delivery['frequency']) === 'fortnightly')
                            <span class="badge bg-{{ $delivery['week_badge'] ?? 'secondary' }}">
                                Week {{ $delivery['week_type'] ?? 'A' }}
                            </span>
                            @if(isset($delivery['should_deliver']) && !$delivery['should_deliver'])
                                <br><small class="text-muted">Skip week</small>
                            @endif
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ isset($delivery['status']) && $delivery['status'] === 'active' ? 'success' : 'warning' }}">
                            {{ ucfirst($delivery['status'] ?? 'pending') }}
                        </span>
                    </td>
                    <td>
                        @if(isset($delivery['id']) && !empty($delivery['id']))
                            <a href="{{ route('admin.users.switch', ['userId' => $delivery['id']]) }}" 
                               class="btn btn-sm btn-outline-primary" 
                               title="Switch to this user's account"
                               target="_blank">
                                <i class="fas fa-user-circle"></i> Switch
                            </a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
