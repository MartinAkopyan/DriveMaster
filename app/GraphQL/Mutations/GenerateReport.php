<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Exceptions\UnauthorizedReportAccessException;
use App\Services\ReportService;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;
use Rebing\GraphQL\Support\SelectFields;

class GenerateReport extends Mutation
{
    protected $attributes = [
        'name' => 'generateReport',
        'description' => 'A mutation'
    ];

    public function __construct(
        private readonly ReportService $reportService,
    ){}

    public function type(): Type
    {
        return GraphQL::type('ReportResponse');
    }

    public function args(): array
    {
        return [
            'report_type' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Type of report: daily, weekly, monthly, custom',
                'rules' => ['required', 'in:daily,weekly,monthly,custom']
            ],
            'date_from' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Start date (Y-m-d)',
                'rules' => ['required', 'date_format:Y-m-d']
            ],
            'date_to' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Start date (Y-m-d)',
                'rules' => ['required', 'date_format:Y-m-d',  'after_or_equal:date_from']
            ]
        ];
    }

    /**
     * @throws UnauthorizedReportAccessException
     */
    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields): array
    {
        $admin = auth()->user();

        $this->reportService->generateAdminReport($admin, $args['report_type'], $args['date_from'], $args['date_to']);

        return [
            'message' => 'Report generation started. You will receive an email with download link.',
            'estimated_time' => '5-10 minutes',
            'report_type' => $args['report_type'],
            'date_from' => $args['date_from'],
            'date_to' => $args['date_to']
        ];
    }
}
