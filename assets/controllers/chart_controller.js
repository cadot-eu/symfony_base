import { Controller } from '@hotwired/stimulus';
import Chart from 'chart.js/auto';

export default class extends Controller {
    static values = {
        values: Array,
        range: String,
    }

    connect() {
        const range = this.rangeValue;
        const isHour = range === 'hour';

        let labels = [];
        let data = [];

        if (isHour) {
            labels = this.valuesValue.map(e => e?.time || 'no-time');
            data = this.valuesValue.map(e => parseFloat(e?.power_w) || 0);
        } else {
            labels = this.valuesValue.map(e => e?.period || 'no-period');
            data = this.valuesValue.map(e => parseFloat(e?.total_kwh) || 0);
        }
        if (range === 'year') {
            labels = labels.map(label => label.slice(0, 7)); // retire heures, garde "YYYY-MM"
        }

        const ctx = this.element.querySelector("canvas");
        if (!ctx) return;

        const getNextRange = (r) => {
            if (r === 'year') return 'month';
            if (r === 'month' || r === 'week') return 'day';
            return null;
        };
        const nextRange = getNextRange(range);

        const generateLink = (label) => {
            let detail = label;
            if (nextRange === 'day') {
                // garde uniquement la date sans l'heure
                detail = label.split(' ')[0];
            } else if (nextRange === 'month') {
                detail = label.slice(0, 7);
            } else if (nextRange === 'year') {
                detail = label.slice(0, 4);
            }
            return `http://localhost/graphique/ballon?device=ballon&range=${nextRange}&detail=${encodeURIComponent(detail)}`;
        };



        new Chart(ctx, {
            type: isHour ? 'line' : 'bar',
            data: {
                labels,
                datasets: [{
                    label: isHour ? 'Puissance (W)' : 'Consommation (kWh)',
                    data,
                    backgroundColor: isHour
                        ? 'rgba(75, 192, 192, 0.2)'
                        : 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y} ${isHour ? 'W' : 'kWh'}`,
                            afterLabel: ctx => {
                                if (nextRange) {
                                    return `Détail: ${generateLink(ctx.label)}`;
                                }
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: isHour ? 'Watts' : 'kWh'
                        },
                        ticks: {
                            callback: value => `${value} ${isHour ? 'W' : 'kWh'}`
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 90,
                            minRotation: 45,
                            callback: function (value) {
                                const label = this.getLabelForValue(value);
                                if (nextRange) {
                                    return `${label}`;
                                }
                                return label;
                            }
                        }
                    }
                },
                onClick: (e, elements) => {
                    if (!elements.length || !nextRange) return;
                    const index = elements[0].index;
                    const label = labels[index];
                    const url = generateLink(label);
                    window.location.href = url;
                }
            }
        });
    }



}
