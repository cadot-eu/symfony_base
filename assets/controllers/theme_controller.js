import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static values = {
        dayStart: Number,
        nightStart: Number
    }

    connect() {
        this.initialAutoTheme = this.getThemeByHour()
        this.applyTheme(this.getStoredOrAutoTheme())

        this.interval = setInterval(() => {
            const currentAuto = this.getThemeByHour()
            const stored = localStorage.getItem("theme-mode")

            if (stored && stored !== currentAuto && currentAuto !== this.initialAutoTheme) {
                // L'heure a changé (ex: jour ↔ nuit) => reset
                localStorage.removeItem("theme-mode")
                this.initialAutoTheme = currentAuto
                this.applyTheme(currentAuto)
            }
        }, 5 * 60 * 1000) // toutes les 5 minutes
    }

    disconnect() {
        clearInterval(this.interval)
    }

    toggle() {
        const newTheme = document.body.classList.contains("theme-day") ? "theme-night" : "theme-day"
        localStorage.setItem("theme-mode", newTheme)
        this.applyTheme(newTheme)
    }

    getStoredOrAutoTheme() {
        return localStorage.getItem("theme-mode") || this.initialAutoTheme
    }

    getThemeByHour() {
        const hour = new Date().getHours()
        const dayStart = this.dayStartValue || 8
        const nightStart = this.nightStartValue || 20
        return (hour >= dayStart && hour < nightStart) ? 'theme-day' : 'theme-night'
    }

    applyTheme(theme) {
        document.body.classList.remove("theme-day", "theme-night")
        document.body.classList.add(theme)

        // Met à jour l'icône si elle existe
        const btn = document.querySelector('[data-action="theme#toggle"] i')
        if (btn) {
            btn.className = theme === 'theme-day' ? 'bi bi-moon' : 'bi bi-sun'
        }
    }
}
