<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Report</title>
    <style>
        body { font-family: Arial, sans-serif; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
<h1>Admin Report: {{ $reportType }}</h1>
<p>Period: {{ $dateFrom->format('Y-m-d') }} to {{ $dateTo->format('Y-m-d') }}</p>

<h2>Lessons Summary</h2>
<table>
    <tr>
        <th>Metric</th>
        <th>Value</th>
    </tr>
    <tr>
        <td>Total Lessons</td>
        <td>{{ $data['total_lessons'] }}</td>
    </tr>
    <tr>
        <td>Confirmed</td>
        <td>{{ $data['confirmed_lessons'] }}</td>
    </tr>
    <tr>
        <td>Completed</td>
        <td>{{ $data['completed_lessons'] }}</td>
    </tr>
    <tr>
        <td>Cancelled</td>
        <td>{{ $data['cancelled_lessons'] }}</td>
    </tr>
</table>

<h2>Instructor Statistics</h2>
<table>
    <tr>
        <th>Status</th>
        <th>Count</th>
    </tr>
    <tr>
        <td>Approved</td>
        <td>{{ $data['instructor_stats']['approved'] }}</td>
    </tr>
    <tr>
        <td>Pending</td>
        <td>{{ $data['instructor_stats']['pending'] }}</td>
    </tr>
    <tr>
        <td>Rejected</td>
        <td>{{ $data['instructor_stats']['rejected'] }}</td>
    </tr>
    <tr>
        <td><strong>Total Applications</strong></td>
        <td><strong>{{ $data['instructor_stats']['total_applications'] }}</strong></td>
    </tr>
</table>

<h2>Top Instructors (by lessons)</h2>
<table>
    <tr>
        <th>Instructor</th>
        <th>Lessons</th>
    </tr>
    @foreach($data['top_instructors'] as $item)
        <tr>
            <td>{{ $item->instructor->name }}</td>
            <td>{{ $item->lessons_count }}</td>
        </tr>
    @endforeach
</table>
</body>
</html>
