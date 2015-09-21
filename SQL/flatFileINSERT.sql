-- Description:	<Swaroop Chakraborty :: The below SQL could be used to auto generate EVTS between a date range with resolution note and store the data in 3 different tables>
CREATE TABLE ##DEMOTEMP
(cust_name varchar(100) NULL,iss_no int NULL,dss_server varchar(300) NULL,db varchar(12) NULL,iss_desc varchar(8000) NULL,res_desc_fin nvarchar(max) NULL,
	OCCUR_DATE nvarchar(max) NULL
)
 DECLARE 
@TSQL   NVARCHAR(MAX)
@Token			                    nvarchar(MAX) ,--used to get Current Upload Token for Security Reasons to prevent leaks into database
@FromDate				    nvarchar(MAX) , --used in EVTSDB IBM DB
@ToDate				            nvarchar(MAX)  --used in EVTSDB IBM DB
SET @TSQL='BULK INSERT ##DSSEVTSTEMP
	   FROM ''D:\MyFiles\EVTSAUTOGENDUMP\EVTSAutoGENInfo.txt''
	  WITH (  FIELDTERMINATOR = ''|'' )'
	  EXEC (@TSQL) 
TRUNCATE TABLE     DemoDataTemp  --purge previuos data
 --Step 1 ::Fetch the EVTSDB data in local table  DemoDataTemp <TEMP SCOPE> which will be TRUNCATED every SP fire.
INSERT INTO DemoDataTemp(cust_name,iss_no,dss_server,db,iss_desc,res_desc_fin,OCCUR_DATE) --full NEW load from a FLAT FILE that has been pushed  into the server.
SELECT cust_name,iss_no,dss_server,db,iss_desc,res_desc_fin,OCCUR_DATE FROM ##DEMOTEMP
 --Step 2::Fetch the EVTSDB data in local table  DemoDataHist which will NOT be TRUNCATED every SP fire. For historical data purpose. No UI use.
INSERT INTO DemoDataHist(cust_name,iss_no,dss_server,db,iss_desc,res_desc_fin,OCCUR_DATE) --STORAGE load
 SELECT cust_name,iss_no,dss_server,db,iss_desc,res_desc_fin,OCCUR_DATE FROM ##DSSEVTSTEMP
--Step 3:: Finally get the view data to the user after truncating the table userResultSetTerminalTemp <TEMP SCOPE> which will be DROPPED every SP fire.
DROP TABLE userResultSetTerminalTemp
--BUSINESS JOIN TO DISPLAY DATA INSERTED FROM THE FLAT FILE
select * into userResultSetTerminalTemp 
from DemoDataTemp a  Cross Join  RawAbendDataPushPoint b  where a.dss_server=b.servername and a.db=b.dbname and b.identityMetric=@Token 
and a.iss_desc like '%' + b.job + '%' and  a.iss_desc like '%' + b.stream + '%' and CONVERT(varchar(12),a.OCCUR_DATE) >= CONVERT(varchar(12),@FromDate) and CONVERT(varchar(12),a.OCCUR_DATE)<= CONVERT(varchar(12),@ToDate)
TRUNCATE TABLE DateRangePushPoint --Release lock on version for use by another user 
 DROP TABLE ##DemoDataTemp --lastly drop the temp table
END
