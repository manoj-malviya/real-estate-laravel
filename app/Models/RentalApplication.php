<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\BlockchainService;

class RentalApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'tenant_id',
        'status',
        'employment_status',
        'annual_income',
        'background_check_status',
        'credit_report_status',
        'smart_contract_address',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function updateStatus($status)
    {
        $this->update(['status' => $status]);

        if ($status === 'approved') {
            $this->deploySmartContract();
        }
    }

    protected function deploySmartContract()
    {
        $blockchainService = new BlockchainService();

        $abi = json_decode(file_get_contents(base_path('contracts/RentalAgreement.abi')), true);
        $bytecode = file_get_contents(base_path('contracts/RentalAgreement.bin'));

        $params = [
            $this->tenant->ethereum_address,
            $this->property->rent_amount,
            $this->property->security_deposit,
            strtotime($this->lease_start_date),
            strtotime($this->lease_end_date)
        ];

        $contractAddress = $blockchainService->deploySmartContract($abi, $bytecode, $params);
        $this->update(['smart_contract_address' => $contractAddress]);
    }
}