import { Capacitor } from '@capacitor/core'

export const getApiUrl = () => {
  // Use your production API when running as native app
  if (Capacitor.isNativePlatform()) {
    return 'https://archangel.darth.cloud/api'
  }
  // Use relative URL for web
  return '/api'
}