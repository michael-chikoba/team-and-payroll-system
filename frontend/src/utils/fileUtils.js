export const fileUtils = {
  downloadBlob(blob, filename) {
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  },

  readFileAsText(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader()
      reader.onload = (event) => resolve(event.target.result)
      reader.onerror = (error) => reject(error)
      reader.readAsText(file)
    })
  },

  readFileAsDataURL(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader()
      reader.onload = (event) => resolve(event.target.result)
      reader.onerror = (error) => reject(error)
      reader.readAsDataURL(file)
    })
  },

  formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes'
    
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
  },

  getFileExtension(filename) {
    return filename.slice((filename.lastIndexOf('.') - 1 >>> 0) + 2)
  },

  isImageFile(filename) {
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']
    const ext = this.getFileExtension(filename).toLowerCase()
    return imageExtensions.includes(ext)
  },

  isPDFFile(filename) {
    const ext = this.getFileExtension(filename).toLowerCase()
    return ext === 'pdf'
  }
}