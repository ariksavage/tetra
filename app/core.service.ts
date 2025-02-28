import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { ErrorService } from '@tetra/error.service';
import { Router } from '@angular/router';


@Injectable({
  providedIn: 'root'
})
export class CoreService {

  private authToken: string = '';

  constructor(
    private errorService: ErrorService,
    private http: HttpClient,
    private route: Router
  ) {}

  getProxy() {

  }

  /**
   * Set messages, return data.
   * @var Object result Response from Core
   */
  handleResult(result: any = null, key: string = '') {
    if (result) {
      if (result.message) {
        let now = new Date();
      let hours = now.getHours();
      let a = hours > 12 ? 'pm' : 'am';
      hours = hours > 12 ? hours - 12 : hours;
      let h = hours.toString().padStart(2, '0');
      let i = now.getMinutes().toString().padStart(2, '0');
      let s = now.getSeconds().toString().padStart(2, '0');
        // console.log(`${h}:${i}:${s}${a}`, result.message);
      }
      if (result.data) {
        return result.data;
      } else {
        return true;
      }
    } else {
      return false;
    }
  }

  /**
   * Set error message
   *
   * and redirect to error page, if necessary.
   *
   * @param Object error Error object
   */
  handleError(url: string, error: any) {
    if (error) {
      this.errorService.setError(error);
      console.error(url, error);
      switch(error.code) {
        case 401:
          this.route.navigateByUrl('/401');
          break;
        case 404:
          this.route.navigateByUrl('/404');
          break;
      }
    }
    return false;
  }

  /**
   * Set authorization token
   * @var String token Token generated by login
   */
  setAuth(token: string) {
    this.authToken = token;
  }

  /**
   * Set headers for http requests
   */
  getConfig() {
    const headers = new HttpHeaders();
    if (this.authToken) {
      headers.set('Authorization', `Bearer ${this.authToken}`);
    }
    // headers.set('responseType',  'application/json');
    return { headers };
  }

  /**
   * Format the core URL
   * @param String type API call type, eg. users, courses
   * @param String action API call action, eg get, list
   * @param Number id ID of the {type} resource
   * @param Number Id2 Secondary ID related to the {action}
   *
   * Example usage:
   *  Get a certificate for a user in a course.
   *  this.url('certifictes', 'course', courseId = 60, userId = 3)
   *  /core/certificates/course/60/3
   */
  url(type: string, action: string = '', id: any = null, id2: any = null) {
    let url = `/core/${type}`;
    if (action) {
      url += `/${action}`;
    }
    if (id) {
      url += `/${id}`;
    }
    if (id2) {
      url += `/${id2}`;
    }
    return url;
  }

  /**
   * http GET request
   *
   * @param String type API call type, eg. users, courses
   * @param String action API call action, eg get, list
   * @param Number id ID of the {type} resource
   * @param Number Id2 Secondary ID related to the {action}
   *
   * @return Promise After handling messages, returns a promise that will resolve data sent back by the API
   */
  get(type: string, action: string = '', id: any = null, id2: any = null) {
    let url = this.url(type, action, id, id2);
    console.log('GET', url);
    return this.http.get(url, this.getConfig()).toPromise().then((result: any) => {
      return this.handleResult(result, type);
    }, (response: any) => {
      return this.handleError(url, response.error)
    });
  }
  /**
   * http PATCH request
   *
   * @param String type API call type, eg. users, courses
   * @param String action API call action, eg get, list
   * @param Number id ID of the {type} resource
   * @param Object payload data to be sent
   *
   * @return Promise After handling messages, returns a promise that will resolve data sent back by the API
   */
  patch(payload: any = {}, type: string, action: string, id: any = null, id2: any = null) {
    let url = this.url(type, action, id, id2);
    return this.http.patch(url, payload, this.getConfig()).toPromise().then((result: any) => {
      return this.handleResult(result, type);
    }, (response: any) => {
      return this.handleError(url, response.error)
    });
  }

  /**
   * http POST request
   *
   * @param String type API call type, eg. users, courses
   * @param String action API call action, eg get, list
   * @param Object payload data to be sent
   *
   * @return Promise After handling messages, returns a promise that will resolve data sent back by the API
   */
  post(type: string, action: string = '', payload: any = {}) {
    let url = this.url(type, action);
    return this.http.post(url, payload, this.getConfig()).toPromise().then((result: any) => {
      return this.handleResult(result, type);
    }, (response: any) => {
      return this.handleError(url, response.error)
    });
  }

  /**
   * http PUT request
   *
   * @param String type API call type, eg. users, courses
   * @param String action API call action, eg get, list
   * @param Number id ID of the {type} resource
   * @param Object payload data to be sent
   *
   * @return Promise After handling messages, returns a promise that will resolve data sent back by the API
   */
  put(type: string, action: string, id: any = null, id2: any = null) {
    let url = this.url(type, action, id, id2);
    return this.http.put(url, null, this.getConfig()).toPromise().then((result: any) => {
      return this.handleResult(result, type);
    }, (response: any) => {
      return this.handleError(url, response.error)
    });
  }

  /**
   * http DELETE request
   *
   * @param String type API call type, eg. users, courses
   * @param String action API call action, eg get, list
   * @param Number id ID of the {type} resource
   * @param Object payload data to be sent
   *
   * @return Promise After handling messages, returns a promise that will resolve data sent back by the API
   */
  delete(type: string, action: string, id: any = null) {
    let url = this.url(type, action, id);
    return this.http.delete(url, this.getConfig()).toPromise().then((result: any) => {
      return this.handleResult(result, type);
    }, (response: any) => {
      return this.handleError(url, response.error)
    });
  }

  /**
   * http GET request and handle file download
   *
   * @param String type API call type, eg. users, courses
   * @param String action API call action, eg get, list
   * @param Number id ID of the {type} resource
   * @param Number Id2 Secondary ID related to the {action}
   *
   * @return Promise Downloads the file and resolves the filename
   */
  // download(type: string, action: string, id: any = null, id2: any = null) {
  //   let url = this.url(type, action, id, id2);
  //   const headers = new HttpHeaders().set('Authorization', 'Bearer ' + this.authToken);
  //   const config = this.getConfig();
  //   return this.http.get(url, { headers: config.headers, observe: 'response', responseType: 'blob' })
  //   .toPromise().then(
  //     (response: any) => {
  //       const contentDispositionHeader = response.headers.get('Content-Disposition');
  //       if (contentDispositionHeader !== null && typeof contentDispositionHeader == 'string') {
  //         // Get filename from the response headers

  //         let fileName = 'download';

  //         const matches = new RegExp('filename="([^"]+)"').exec(contentDispositionHeader);
  //         if (matches && matches.length > 0){
  //           fileName = matches[1];
  //         }

  //         // Download the file
  //         const file = new Blob([response.body]);
  //         saveAs(file, fileName);

  //         return fileName;
  //       } else {
  //         this.messages.error("Unable to download file");
  //         return false;
  //       }
  //     },
  //     (response: any) => {
  //       return this.handleError(url, response.error);
  //     }
  //   );
  // }
}
