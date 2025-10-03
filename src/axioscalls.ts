import axios from 'axios'
import type { AxiosResponse, AxiosError } from 'axios'
export interface TypeAffaire {
    value: number
    title: string
}
export interface AffaireTypesChange {
    success: boolean
    message: string
    id: number
    idtype: number
    nom: string
    type: string
    btermine: boolean
    typesfinal?: TypeAffaire[]
}
export interface ApiResponseATC {
    success: boolean
    message: string
    data?: AffaireTypesChange
}
// Interface générique pour les réponses API
interface ApiResponse<T> {
    success: boolean
    message: string
    data?: T
}

export async function getAffaireChangeTypeData(server: string = '', page: string, jsonCriteres: string = '{}'): Promise<ApiResponseATC> {
    const urlol: string = `${server}${page}`
    const params = new URLSearchParams([['jsoncriteres', jsonCriteres]])
    try {
        const response: AxiosResponse<AffaireTypesChange> = await axios.get(urlol, { params })
        const respData: ApiResponseATC = {
            "success": response.data.success,
            "message": response.data.message,
            "data": response.data
        }
        return respData
    } catch (error) {
        return traiteAxiosError(error as AxiosError)
    }
}

function traiteAxiosError<T>(error: AxiosError): ApiResponse<T> {
    let msgErr: string = ''
    if (error.response) {
        msgErr = `${error.response.data}\nstatus: ${error.response.status}\n${error.response.headers}`
    } else if (error.request.responseText) {
        msgErr = error.request.responseText
    } else {
        msgErr = error.message
    }
    const respData: ApiResponse<T> = {
        "success": false,
        "message": `ERREUR. ${msgErr}`,
    }
    console.log(respData)
    return respData
}