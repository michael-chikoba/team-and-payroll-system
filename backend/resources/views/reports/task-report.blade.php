<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $reportData['title'] }}</title>
    <style>
        @page {
            margin: 10mm;
            size: A4 portrait;
        }
        @font-face {
            font-family: 'DejaVu Sans';
            font-style: normal;
            font-weight: normal;
            src: url('{{ storage_path('fonts/DejaVuSans.ttf') }}') format('truetype');
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica', sans-serif;
            font-size: 8.5pt;
            line-height: 1.3;
            color: #2d3748;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* --- Header Section --- */
        .header {
            margin-bottom: 8pt;
            border-bottom: 1.5pt solid #3182ce;
            padding-bottom: 4pt;
        }
        .header h1 {
            color: #1a202c;
            font-size: 16pt;
            margin: 0;
            display: inline-block;
        }
        .header-subtitle {
            float: right;
            color: #718096;
            font-size: 8pt;
            margin-top: 6pt;
        }

        /* --- Compact Metadata Bar --- */
        .metadata {
            width: 100%;
            margin-bottom: 10pt;
            background-color: #f8fafc;
            border: 0.5pt solid #e2e8f0;
            border-radius: 2pt;
        }
        .metadata-table {
            width: 100%;
            border-collapse: collapse;
        }
        .metadata-item {
            padding: 4pt 8pt;
            border-right: 0.5pt solid #e2e8f0;
        }
        .metadata-item:last-child { border-right: none; }
        .metadata-label {
            font-size: 6.5pt;
            color: #718096;
            text-transform: uppercase;
            font-weight: bold;
        }
        .metadata-value {
            font-weight: bold;
            color: #2d3748;
            margin-left: 4pt;
        }

        /* --- Summary Cards --- */
        .section-header {
            color: #2d3748;
            font-size: 10pt;
            font-weight: bold;
            margin: 8pt 0 4pt 0;
            padding-bottom: 2pt;
            border-bottom: 0.5pt solid #e2e8f0;
        }
        .summary-card {
            background: #ffffff;
            border: 0.5pt solid #edf2f7;
            padding: 4pt;
            text-align: center;
            border-radius: 2pt;
        }
        .summary-label {
            font-size: 6.5pt;
            color: #718096;
            display: block;
        }
        .summary-value {
            font-size: 11pt;
            font-weight: bold;
            color: #2b6cb0;
        }

        /* --- Data Tables --- */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8pt;
        }
        .data-table th {
            background-color: #f7fafc;
            padding: 4pt;
            text-align: left;
            font-size: 7.5pt;
            border-bottom: 1pt solid #cbd5e0;
        }
        .data-table td {
            padding: 3pt 4pt;
            border-bottom: 0.5pt solid #edf2f7;
        }

        /* --- Compact Task Items --- */
        .task-item {
            margin-bottom: 6pt;
            border: 0.5pt solid #e2e8f0;
            border-radius: 3pt;
            padding: 6pt;
            page-break-inside: avoid;
        }
        .task-title-row { margin-bottom: 3pt; }
        .task-title { font-size: 10pt; font-weight: bold; color: #1a202c; }
        .task-id { color: #a0aec0; font-size: 8pt; margin-left: 4pt; }

        .badge {
            display: inline-block;
            padding: 1pt 4pt;
            border-radius: 2pt;
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-right: 2pt;
        }
        
        .priority-critical { background: #fff5f5; color: #c53030; border: 0.5pt solid #feb2b2; }
        .priority-high { background: #fffaf0; color: #9c4221; border: 0.5pt solid #fbd38d; }
        .status-badge { background: #edf2f7; color: #4a5568; border: 0.5pt solid #cbd5e0; }

        .task-description {
            background: #f8fafc;
            padding: 5pt;
            border-left: 1.5pt solid #cbd5e0;
            margin: 4pt 0;
            font-size: 8pt;
            color: #4a5568;
        }

        .subtasks-section, .comments-section {
            margin-top: 4pt;
            padding: 4pt;
            background: #f8fafc;
            border: 0.5pt solid #e2e8f0;
        }
        .subtask-item, .comment-item {
            padding: 2pt 0;
            border-bottom: 0.5pt dashed #e2e8f0;
        }
        .subtask-item:last-child, .comment-item:last-child { border-bottom: none; }

        .footer {
            margin-top: 15pt;
            text-align: center;
            font-size: 7pt;
            color: #a0aec0;
            border-top: 0.5pt solid #e2e8f0;
            padding-top: 4pt;
        }
        .text-right { text-align: right; }
        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>

    <div class="header clearfix">
        <h1>{{ $reportData['title'] }}</h1>
        <div class="header-subtitle">
            <strong>{{ $reportData['business_name'] }}</strong> • {{ $reportData['generated_by'] }}
        </div>
    </div>

    <div class="metadata">
        <table class="metadata-table">
            <tr>
                <td class="metadata-item">
                    <span class="metadata-label">Date:</span>
                    <span class="metadata-value">{{ $reportData['generated_at'] }}</span>
                </td>
                <td class="metadata-item">
                    <span class="metadata-label">Tasks:</span>
                    <span class="metadata-value">{{ $reportData['total_tasks'] }}</span>
                </td>
                <td class="metadata-item">
                    <span class="metadata-label">Completion:</span>
                    <span class="metadata-value">{{ $reportData['summary']['completion_rate'] }}%</span>
                </td>
                <td class="metadata-item">
                    <span class="metadata-label">Work Hours:</span>
                    <span class="metadata-value">{{ $reportData['summary']['total_work_hours'] }}h</span>
                </td>
            </tr>
        </table>
    </div>

    <table style="width: 100%; border-collapse: separate; border-spacing: 3pt;">
        <tr>
            <td class="summary-card"><span class="summary-label">To Do</span><div class="summary-value">{{ $reportData['summary']['status_counts']['todo'] }}</div></td>
            <td class="summary-card"><span class="summary-label">In Progress</span><div class="summary-value">{{ $reportData['summary']['status_counts']['in_progress'] }}</div></td>
            <td class="summary-card"><span class="summary-label">Review</span><div class="summary-value">{{ $reportData['summary']['status_counts']['under_review'] }}</div></td>
            <td class="summary-card"><span class="summary-label">Done</span><div class="summary-value">{{ $reportData['summary']['status_counts']['completed'] }}</div></td>
        </tr>
    </table>

    <div class="section-header">Assignee Activity</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Team Member</th>
                <th class="text-right">Tasks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reportData['detailed_summary']['tasks_by_assignee'] as $assignee => $count)
            <tr>
                <td>{{ $assignee }}</td>
                <td class="text-right"><strong>{{ $count }}</strong></td>
            </tr>
            @empty
            <tr><td colspan="2">No activity recorded</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-header">Detailed Task List</div>
    @forelse($reportData['tasks'] as $task)
        <div class="task-item {{ $task['is_overdue'] ? 'overdue' : '' }}">
            <div class="task-title-row">
                <span class="badge status-badge">{{ $task['status'] }}</span>
                <span class="task-title">{{ $task['title'] }}</span>
                <span class="task-id">#{{ $task['id'] }}</span>
                @if($task['is_overdue'])
                    <span class="badge priority-critical" style="float:right">OVERDUE</span>
                @endif
            </div>

            <div style="font-size: 7.5pt; color: #718096; margin: 2pt 0;">
                <span class="badge priority-{{ strtolower($task['priority']) }}">{{ $task['priority'] }}</span>
                <strong>Assignee:</strong> {{ $task['assigned_to'] }} | 
                <strong>Due:</strong> {{ $task['deadline'] }} | 
                <strong>Hours:</strong> {{ $task['total_hours_logged'] }}h
            </div>

            @if(!empty($task['description']))
                <div class="task-description">
                    {!! $task['html_description'] !!}
                </div>
            @endif

            @if(($options['include_subtasks'] ?? false) && !empty($task['subtasks']))
                <div class="subtasks-section">
                    <div style="font-weight:bold; font-size:7pt; margin-bottom:2pt; color:#4a5568">Subtasks ({{ count($task['subtasks']) }})</div>
                    @foreach($task['subtasks'] as $subtask)
                        <div class="subtask-item">
                            <span style="font-weight:600">{{ $subtask['title'] }}</span> 
                            <span style="font-size:7pt; color:#a0aec0">({{ $subtask['status'] }})</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <div style="text-align: center; padding: 10pt; color: #a0aec0;">No tasks found.</div>
    @endforelse

    <div class="footer">
        {{ $reportData['business_name'] }} • Confidential Report • Generated {{ $reportData['generated_at'] }}
    </div>

</body>
</html>