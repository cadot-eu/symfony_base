import { Controller } from "@hotwired/stimulus"
import mqtt from "mqtt"

export default class extends Controller {
    static targets = ["modal", "commandProperty", "actionType", "returnInput"]

    connect() {
        this.client = null
        this.currentTopic = null
        this.connectMQTT()
    }

    openModal() {
        const modalElement = this.modalTarget
        const modal = new bootstrap.Modal(modalElement)
        modal.show()
    }

    connectMQTT() {
        try {
            const brokerUrl = document.getElementById("mqtturl").value.trim()
            if (!brokerUrl) return

            this.brokerValue = 'ws://' + brokerUrl + ':8083'
            this.client = mqtt.connect(this.brokerValue, {
                username: "mickadmin",
                password: "m",
                keepalive: 60,
                reconnectPeriod: 1000,
                connectTimeout: 30 * 1000
            })

            this.client.on("connect", () => {
                console.log("✅ MQTT connecté")
            })

            this.client.on("message", (topic, message) => {
                if (this.currentTopic && topic === this.currentTopic) {
                    let msgStr = message.toString()
                    this.returnInputTarget.value = msgStr
                }
            })

        } catch (e) {
            console.error("❌ Erreur lors de la connexion MQTT :", e)
        }
    }

    sendCommand(event) {
        event.preventDefault()

        if (!this.client || !this.client.connected) {
            alert("⚠️ Vous devez être connecté à MQTT avant d'envoyer une commande.")
            return
        }

        const deviceName = "temperature_salon" // 👈 Change selon ton besoin ou rends-le dynamique si nécessaire
        const action = this.actionTypeTarget.value
        const property = this.commandPropertyTarget.value.trim()

        if (!property) {
            alert("❗ Veuillez entrer une propriété ou une valeur.")
            return
        }

        let topicToSend, payloadToSend

        if (action === "get") {
            // Exemple : zigbee2mqtt/salon/get/temperature
            topicToSend = `zigbee2mqtt/${deviceName}/get/${property}`
            payloadToSend = '{}'

            // S'abonne au retour automatique
            this.currentTopic = `zigbee2mqtt/${deviceName}`

            this.client.subscribe(this.currentTopic, err => {
                if (err) {
                    console.error("❌ Erreur abonnement :", err)
                }
            })

        } else if (action === "set") {
            // Exemple : zigbee2mqtt/salon/set avec payload JSON
            topicToSend = `zigbee2mqtt/${deviceName}/set`
            payloadToSend = property // ex: { "state": "ON" }

            this.currentTopic = `zigbee2mqtt/${deviceName}`

            this.client.subscribe(this.currentTopic, err => {
                if (err) {
                    console.error("❌ Erreur abonnement :", err)
                }
            })
        }

        // Envoie la commande
        this.client.publish(topicToSend, payloadToSend, (err) => {
            if (err) {
                console.error("❌ Échec de l'envoi :", err)
                alert("Erreur lors de l'envoi de la commande.")
            } else {
                console.log(`📤 Commande envoyée sur ${topicToSend}`)
            }
        })
    }
}