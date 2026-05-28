document.addEventListener("DOMContentLoaded", async () => {
    try {
        const stats = await EffectaAPI.request('api/index.php?action=get_dashboard_stats');
        
        // Populate cards
        document.getElementById('totalProjects').textContent = stats.total_projects;
        document.getElementById('totalRegisters').textContent = stats.total_registers;
        document.getElementById('onTimeMonth').textContent = stats.on_time_month;
        document.getElementById('delayedMonth').textContent = stats.delayed_month;

        renderChart(stats.chart_data);
    } catch (err) {
        console.error("Erro ao carregar dashboard:", err);
        showToast("Erro ao carregar dados do dashboard.", "error");
    }
});

function renderChart(data) {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    const isDark = document.documentElement.classList.contains('dark');
    
    const colors = {
        onTime: '#10b981',   // emerald-500
        delayed: '#ef4444',  // red-500
        pending: '#f59e0b'   // amber-500
    };

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Registros',
                data: data.values,
                backgroundColor: [colors.onTime, colors.delayed, colors.pending],
                borderRadius: 12,
                borderSkipped: false,
                barThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: isDark ? '#1e293b' : '#fff',
                    titleColor: isDark ? '#fff' : '#1e293b',
                    bodyColor: isDark ? '#94a3b8' : '#64748b',
                    borderColor: isDark ? '#334155' : '#e2e8f0',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: isDark ? '#94a3b8' : '#64748b',
                        font: { family: 'Inter', weight: 'bold' }
                    }
                },
                y: {
                    grid: { 
                        color: isDark ? '#334155' : '#f1f5f9',
                        drawBorder: false 
                    },
                    ticks: {
                        color: isDark ? '#94a3b8' : '#64748b',
                        stepSize: 1,
                        beginAtZero: true
                    }
                }
            }
        }
    });
}
